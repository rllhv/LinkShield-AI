<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\SecurityInvestigationService;
use App\Models\Scan;

class ScanController extends Controller
{
    private SecurityInvestigationService $investigationService;

    public function __construct(SecurityInvestigationService $investigationService)
    {
        $this->investigationService = $investigationService;
    }

    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|string|max:2048',
        ]);

        $url = $request->input('url');

        // Normalize URL
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }

        $user = $request->user('sanctum');
        $userId = $user ? $user->id : null;

        // Cache lookup (valid for 7 days)
        $latestScan = \App\Models\Scan::where('url', $url)->latest('updated_at')->first();
        if ($latestScan && $latestScan->updated_at->greaterThan(now()->subDays(7))) {
            $results = $latestScan->results;
            
            // Re-generate if AI failed previously
            $aiMsg = $results['aiSummary']['en'] ?? '';
            $isAiFailed = str_contains($aiMsg, 'AI summary failed') || str_contains($aiMsg, 'AI analysis unavailable');
            
            if (!$isAiFailed) {
                // Found in cache, save a record for this user (or update anonymous one)
                $scanRecord = \App\Models\Scan::updateOrCreate(
                    ['url' => $url, 'user_id' => $userId],
                    ['results' => $results]
                );
                $scanRecord->search_count = ($scanRecord->search_count ?? 0) + 1;
                $scanRecord->save();
                
                $results['url'] = $url;
                $results['search_count'] = $scanRecord->search_count;
                return response()->json($results);
            }
        }

        // If not cached or older than 7 days, investigate
        $results = $this->investigationService->investigate($url);

        // Save or update in database
        $scan = \App\Models\Scan::updateOrCreate(
            ['url' => $url, 'user_id' => $userId],
            ['results' => $results]
        );
        $scan->search_count = ($scan->search_count ?? 0) + 1;
        $scan->save();
        
        $results['id'] = $scan->id;
        $results['search_count'] = $scan->search_count;

        return response()->json($results);
    }

    public function history()
    {
        // Privacy: the public feed only lists anonymous scans. Scans that belong
        // to a logged-in user are private and are served by myScans() instead.
        $paginator = \App\Models\Scan::whereNull('user_id')
            ->orderBy('updated_at', 'desc')
            ->paginate(20, ['id', 'url', 'results', 'updated_at', 'search_count']);
        
        $paginator->getCollection()->transform(function ($scan) {
            $trustScore = 0;
            if (isset($scan->results['trustScore'])) {
                 $trustScore = $scan->results['trustScore'];
            } else if (isset($scan->results['engines'])) {
                 $trustScore = $this->calculateFallbackTrustScore($scan->results);
            }

            return [
                'id' => $scan->id,
                'url' => $scan->url,
                'score' => $trustScore,
                'aiSummary' => $scan->results['aiSummary'] ?? null,
                'search_count' => $scan->search_count ?? 1,
                'updated_at' => $scan->updated_at,
            ];
        });

        return response()->json($paginator);
    }

    public function linkScans(Request $request): JsonResponse
    {
        $request->validate([
            'urls' => 'required|array',
            'urls.*' => 'string'
        ]);

        $user = $request->user();
        
        $updated = Scan::whereIn('url', $request->urls)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);

        return response()->json(['message' => 'Scans linked', 'count' => $updated]);
    }

    private function calculateFallbackTrustScore($data)
    {
        if (($data['engines']['infra']['status'] ?? '') === 'unresolved' || ($data['engines']['urlScan']['isUnreachable'] ?? false)) return -1;
        $score = 100;
        $vtStatus = $data['engines']['virusTotal']['status'] ?? null;
        if (isset($data['engines']['virusTotal']) && $vtStatus !== 'not_scanned') {
            $mInd = 0; $sInd = 0;
            if (isset($data['engines']['virusTotalResults'])) {
                foreach ($data['engines']['virusTotalResults'] as $r) {
                    if (strtolower($r['result']) === 'malicious' || strtolower($r['category'] ?? '') === 'malicious') $mInd++;
                    if (strtolower($r['result']) === 'suspicious' || strtolower($r['category'] ?? '') === 'suspicious') $sInd++;
                }
            }
            $tMal = max($data['engines']['virusTotal']['malicious'] ?? 0, $mInd);
            $tSus = max($data['engines']['virusTotal']['suspicious'] ?? 0, $sInd);
            if ($tMal > 0) $score -= ($tMal <= 3) ? ($tMal * 10) : (30 + ($tMal - 3) * 5);
            if ($tSus > 0) $score -= ($tSus * 2);
            if ($score >= 100 && ($tMal > 0 || $tSus > 0)) $score = 98;
        }
        if (!empty($data['engines']['safeBrowsing'])) $score -= 50;
        return max(0, min(100, round($score)));
    }

    public function stats()
    {
        $scans = \App\Models\Scan::all();
        $total = $scans->count();
        $safe = 0;
        $malicious = 0;

        foreach ($scans as $scan) {
            $trustScore = $scan->results['trustScore'] ?? $this->calculateFallbackTrustScore($scan->results);
            if ($trustScore > 80) {
                $safe++;
            } else {
                $malicious++;
            }
        }

        return response()->json([
            'totalAnalyses' => $total,
            'safeAnalyses' => $safe,
            'maliciousAnalyses' => $malicious,
            'aiAnalyses' => $total, // Every scan runs through AI
            'activeNodes' => 4 // VT, URLScan, GSB, Gemini
        ]);
    }

    public function myScans(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $paginator = $user->scans()->orderBy('updated_at', 'desc')->paginate(20, ['id', 'url', 'results', 'updated_at', 'search_count']);
        
        $paginator->getCollection()->transform(function ($scan) {
            $trustScore = 0;
            if (isset($scan->results['trustScore'])) {
                 $trustScore = $scan->results['trustScore'];
            } else if (isset($scan->results['engines'])) {
                 $trustScore = $this->calculateFallbackTrustScore($scan->results);
            }

            return [
                'id' => $scan->id,
                'url' => $scan->url,
                'score' => $trustScore,
                'aiSummary' => $scan->results['aiSummary'] ?? null,
                'search_count' => $scan->search_count ?? 1,
                'updated_at' => $scan->updated_at,
            ];
        });

        return response()->json($paginator);
    }
}
