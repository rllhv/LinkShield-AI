<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Log;

class SecurityInvestigationService
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'timeout'         => 15, // Increased from 10
            'connect_timeout' => 8,  // Increased from 5
            'verify'          => true,
        ]);
    }

    public function investigate(string $url): array
    {
        set_time_limit(90); // Allow more time for all APIs to respond
        $targetDomain = $this->extractDomain($url);

        $results = [
            'url'       => $url,
            'timestamp' => now()->toISOString(),
            'engines'   => [],
        ];

        // Run all intelligence phases in parallel to prevent 504 Gateway Timeout
        $phases = \Illuminate\Support\Facades\Concurrency::run([
            'infra' => fn () => $this->traceInfrastructure($targetDomain),
            'vt' => function () use ($url) {
                $vtKey = config('services.virustotal.key');
                return $vtKey ? $this->queryVirusTotal($url, $vtKey) : [];
            },
            'urlScan' => function () use ($url) {
                $urlScanKey = config('services.urlscan.key');
                if ($urlScanKey && strlen($urlScanKey) > 10) {
                    return $this->observeUrlScan($url, $urlScanKey);
                }
                return ['urlScan' => ['fallbackScreenshot' => "https://image.thum.io/get/width/1200/crop/800/{$url}"]];
            },
            'gsb' => function () use ($url) {
                $gsbKey = config('services.safe_browsing.key');
                return $gsbKey ? $this->querySafeBrowsing($url, $gsbKey) : [];
            }
        ]);

        // Merge results from parallel phases
        $results['engines']['infra'] = $phases['infra'] ?? [];
        $results['engines'] = array_merge($results['engines'], $phases['vt'] ?? []);
        
        $urlScanData = $phases['urlScan'] ?? [];
        $results['engines']['urlScan'] = $urlScanData['urlScan'] ?? null;
        if (isset($urlScanData['infra'])) {
            $results['engines']['infra'] = array_merge($results['engines']['infra'], $urlScanData['infra']);
        }
        
        $results['engines']['safeBrowsing'] = $phases['gsb'] ?? [];

        // Final Phase: AI Summary Generation (Gemini)
        $results['aiSummary'] = $this->generateAiSummary($url, $results['engines'], config('services.gemini.key'));

        return $results;
    }

    public function generateAiSummary(string $url, array $enginesData, ?string $geminiKey): array
    {
        $fallback = [
            'en' => 'AI summary failed to generate, but technical data is available.',
            'ar' => 'تعذر إنشاء ملخص للذكاء الاصطناعي، البيانات التقنية متوفرة أدناه.'
        ];

        if (!$geminiKey) {
            return [
                'en' => 'AI analysis unavailable.',
                'ar' => 'تحليل الذكاء الاصطناعي غير متاح حالياً.'
            ];
        }

        // Using a broad mix of Flash and Pro models across versions to maximize free tier availability
        $models = [
            'gemini-1.5-flash', 
            'gemini-1.5-flash-8b', 
            'gemini-3-flash-preview', 
            'gemini-2.5-flash', 
            'gemini-1.0-pro', 
            'gemini-pro'
        ]; 
        $startTime = microtime(true);

        foreach ($models as $modelName) {
            // Stop if we are approaching the 60s gateway timeout (leave 5s buffer)
            if (microtime(true) - $startTime > 45) break;

            $modelAttempts = 0;
            while ($modelAttempts < 2) { // 2 attempts per model to handle transient DNS blips
                try {
                    $isUnreachable = ($enginesData['infra']['status'] ?? '') === 'unresolved' || ($enginesData['urlScan']['isUnreachable'] ?? false);
                    
                    $prompt = "Analyze these security results for the URL: {$url}. \n";
                    $prompt .= "Results: " . json_encode($enginesData) . ". \n";
                    if ($isUnreachable) {
                        $prompt .= "CRITICAL: The domain is UNREACHABLE (DNS failure or not registered). \n";
                    }
                    $prompt .= "Provide a 2-sentence natural language security summary. Be concise, professional, and highlight major risks if found. \n";
                    $prompt .= "If the domain is unreachable/unregistered, state clearly that it is not an active website.\n";
                    $prompt .= "IMPORTANT: You MUST return your response as a valid JSON object with exactly two keys: 'en' for the English summary, and 'ar' for the Arabic translation of the summary. Do not include any markdown formatting or backticks around the JSON.";

                    // Inject a custom Guzzle client that bypasses local DNS using known Google IPs
                    // This prevents cURL error 6 (Could not resolve host) in local dev environments
                    $dnsResolvemap = [
                        'generativelanguage.googleapis.com:443:142.250.200.170',
                        'generativelanguage.googleapis.com:443:142.251.37.74',
                    ];
                    $customGuzzle = new Client([
                        'timeout'         => 20,
                        'connect_timeout' => 10,
                        'verify'          => true,
                        'curl'            => [
                            CURLOPT_RESOLVE => $dnsResolvemap,
                        ],
                    ]);

                    $client = \Gemini::factory()
                        ->withApiKey($geminiKey)
                        ->withHttpClient($customGuzzle)
                        ->make();

                    $response = $client->generativeModel($modelName)->generateContent($prompt);

                    $text = trim($response->text());
                    $text = preg_replace('/^```json\s*/', '', $text);
                    $text = preg_replace('/^```\s*/', '', $text);
                    $text = preg_replace('/\s*```$/', '', $text);

                    $decoded = json_decode($text, true);

                    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['en']) && isset($decoded['ar'])) {
                        return $decoded;
                    }
                    break; // Format error, try next model
                } catch (\Throwable $e) {
                    $errMsg = $e->getMessage();
                    Log::error("Gemini AI Model {$modelName} Error: " . $errMsg);
                    
                    // If it's a DNS error (cURL 6), wait 1s and retry this specific model once
                    if (str_contains(strtolower($errMsg), 'could not resolve host') && $modelAttempts < 1) {
                        $modelAttempts++;
                        sleep(1);
                        continue;
                    }
                    
                    $lastError = $errMsg;
                    break; // Move to next model
                }
            }
        }

        if (str_contains(strtolower($lastError ?? ''), 'could not resolve host')) {
            $fallback['en'] = 'AI Analysis Delayed: Encountered a local network DNS conflict while contacting Google Intelligence. Technical data remains available below.';
            $fallback['ar'] = 'تأخر تحليل الذكاء الاصطناعي: واجه النظام تعارضاً في شبكة DNS المحلية أثناء الاتصال بخدمات جوجل. لا تزال البيانات التقنية متوفرة أدناه.';
            $lastError = 'Local DNS Conflict (Check internet connection)';
        }

        $fallback['error'] = $lastError ?? 'AI Generation Timeout (Prevented 504)';
        return $fallback;
    }


    private function extractDomain(string $url): string
    {
        $domainMatch = [];
        preg_match('/^(?:https?:\/\/)?(?:www\.)?([^\/]+)/i', $url, $domainMatch);
        return $domainMatch[1] ?? $url;
    }

    private function traceInfrastructure(string $targetDomain): array
    {
        try {
            $infraRes = $this->http->get(
                "http://ip-api.com/json/{$targetDomain}?fields=status,message,country,city,as,query",
                ['timeout' => 5] // Increased from 3
            );
            $infraData = json_decode($infraRes->getBody(), true);

            if (($infraData['status'] ?? '') === 'success') {
                $asParts  = explode(' ', $infraData['as'] ?? '');
                return [
                    'ip'      => $infraData['query']   ?? null,
                    'asn'     => $asParts[0]            ?? null,
                    'asnName' => implode(' ', array_slice($asParts, 1)) ?: null,
                    'country' => $infraData['country'] ?? null,
                    'city'    => $infraData['city']    ?? null,
                    'domain'  => $targetDomain,
                    'status'  => 'resolved',
                ];
            } else {
                return [
                    'status' => 'unresolved',
                    'error'  => $infraData['message'] ?? 'DNS Resolution Failed',
                    'domain' => $targetDomain,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Infra fallback failed: ' . $e->getMessage());
            return ['status' => 'error', 'domain' => $targetDomain];
        }
    }

    private function queryVirusTotal(string $url, string $vtKey): array
    {
        $result = [];
        try {
            $urlId  = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($url));
            $vtRes  = $this->http->get(
                "https://www.virustotal.com/api/v3/urls/{$urlId}",
                ['headers' => ['x-apikey' => $vtKey]]
            );
            $vtData = json_decode($vtRes->getBody(), true);
            $attrs  = $vtData['data']['attributes'] ?? [];

            $result['virusTotal'] = array_merge(
                $attrs['last_analysis_stats'] ?? [],
                ['times_submitted' => $attrs['times_submitted'] ?? 1]
            );
            $result['virusTotalResults'] = $attrs['last_analysis_results'] ?? null;
        } catch (RequestException $e) {
            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 404) {
                $result['virusTotal'] = [
                    'status'  => 'not_scanned',
                    'message' => 'URL has not been previously analyzed by VirusTotal.',
                ];
            } else {
                Log::error('VT Error: ' . $e->getMessage());
                $result['virusTotal'] = ['error' => 'Failed to fetch VirusTotal data'];
            }
        } catch (\Throwable $e) {
            Log::error('VT Error: ' . $e->getMessage());
            $result['virusTotal'] = ['error' => 'Failed to fetch VirusTotal data'];
        }
        return $result;
    }

    private function observeUrlScan(string $url, string $urlScanKey): array
    {
        $result = [];
        try {
            // Search for existing scan
            $searchResults = [];
            $domain = $this->extractDomain($url);
            try {
                $searchHttp = $this->http->get(
                    'https://urlscan.io/api/v1/search/',
                    [
                        'query'   => ['q' => "domain:\"{$domain}\"", 'size' => 1],
                        'headers' => ['API-Key' => $urlScanKey],
                    ]
                );
                $searchData    = json_decode($searchHttp->getBody(), true);
                $searchResults = $searchData['results'] ?? [];
            } catch (\Throwable $e) {
                Log::warning('URLScan search rejected: ' . $e->getMessage());
            }

            if (!empty($searchResults)) {
                $lastScan = $searchResults[0];
                $uuid     = $lastScan['task']['uuid'];
                $result['urlScan'] = [
                    'uuid'       => $uuid,
                    'url'        => $lastScan['task']['url'],
                    'screenshot' => "https://urlscan.io/screenshots/{$uuid}.png",
                    'cached'     => true,
                ];
                // Enrich infra
                $page = $lastScan['page'] ?? [];
                $result['infra'] = array_filter([
                    'ip'      => $page['ip']      ?? null,
                    'asn'     => $page['asn']     ?? null,
                    'asnName' => $page['asnname'] ?? null,
                    'server'  => $page['server']  ?? null,
                    'country' => $page['country'] ?? null,
                    'city'    => $page['city']    ?? null,
                    'domain'  => $page['domain']  ?? null,
                ]);
            } else {
                // Trigger new scan
                try {
                    $scanRes  = $this->http->post(
                        'https://urlscan.io/api/v1/scan/',
                        [
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'API-Key'      => $urlScanKey,
                            ],
                            'json' => ['url' => $url, 'visibility' => 'unlisted'],
                        ]
                    );
                    $scanData = json_decode($scanRes->getBody(), true);
                    $uuid     = $scanData['uuid'];
                    $result['urlScan'] = [
                        'uuid'       => $uuid,
                        'status'     => 'scanning',
                        'screenshot' => "https://urlscan.io/screenshots/{$uuid}.png",
                        'cached'     => false,
                    ];
                } catch (RequestException $e) {
                    $errBody = $e->getResponse()
                        ? json_decode($e->getResponse()->getBody(), true)
                        : [];
                    $errMsg  = $errBody['message'] ?? $errBody['title'] ?? $e->getMessage();
                    $errLow  = strtolower($errMsg);

                    if (str_contains($errLow, 'dns error') || str_contains($errLow, 'resolve domain')) {
                        $result['urlScan'] = [
                            'error'          => 'Domain Unreachable',
                            'isUnreachable'  => true,
                            'fallbackScreenshot' => null,
                        ];
                    } elseif (str_contains($errLow, 'scan prevented') || str_contains($errLow, 'disallowed')) {
                        $result['urlScan'] = [
                            'error'              => 'Policy Restricted',
                            'isRestricted'       => true,
                            'fallbackScreenshot' => "https://image.thum.io/get/width/1200/crop/800/{$url}",
                        ];
                    } else {
                        Log::warning('URLScan POST failed: ' . $errMsg);
                        $result['urlScan'] = [
                            'error'              => 'Investigation bypassed',
                           'fallbackScreenshot' => "https://image.thum.io/get/width/1200/crop/800/{$url}",
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('URLScan System Fault: ' . $e->getMessage());
            $result['urlScan'] = [
                'error'              => 'Investigation bypassed',
                'fallbackScreenshot' => "https://image.thum.io/get/width/1200/crop/800/{$url}",
            ];
        }
        return $result;
    }

    private function querySafeBrowsing(string $url, string $gsbKey): array
    {
        try {
            $gsbRes = $this->http->post(
                "https://safebrowsing.googleapis.com/v4/threatMatches:find?key={$gsbKey}",
                [
                    'json' => [
                        'client'     => ['clientId' => 'linkshield-ai', 'clientVersion' => '1.0.0'],
                        'threatInfo' => [
                            'threatTypes'      => ['MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE', 'POTENTIALLY_HARMFUL_APPLICATION'],
                            'platformTypes'    => ['ANY_PLATFORM'],
                            'threatEntryTypes' => ['URL'],
                            'threatEntries'    => [['url' => $url]],
                        ],
                    ],
                ]
            );
            $gsbData = json_decode($gsbRes->getBody(), true);
            return $gsbData['matches'] ?? [];
        } catch (\Throwable $e) {
            Log::error('GSB Error: ' . $e->getMessage());
            return ['error' => 'Failed to fetch Safe Browsing data'];
        }
    }
}
