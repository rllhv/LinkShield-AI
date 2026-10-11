<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Post;
use App\Models\User;
use App\Models\Scan;

class AdminController extends Controller
{
    public function stats(): JsonResponse
    {
        $totalUsers = User::count();
        $totalScans = Scan::sum('search_count') ?? Scan::count();
        $pendingPosts = Post::where('status', 'pending')->count();

        // Rough safe vs malicious ratio
        $maliciousScans = Scan::where('results', 'LIKE', '%"trustScore":%')->get()->filter(function ($scan) {
            $score = $scan->results['trustScore'] ?? 100;
            return $score < 50;
        })->count();

        $safeScans = $totalScans - $maliciousScans;
        $safeRatio = $totalScans > 0 ? round(($safeScans / $totalScans) * 100) : 100;

        return response()->json([
            'totalUsers' => $totalUsers,
            'totalScans' => $totalScans,
            'pendingPosts' => $pendingPosts,
            'safeRatio' => $safeRatio,
        ]);
    }

    public function pendingPosts(): JsonResponse
    {
        $posts = Post::with('user:id,name,email')
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        $posts->getCollection()->transform(function ($post) {
            if ($post->image_path) {
                $post->image_url = asset('storage/' . $post->image_path);
            }
            return $post;
        });

        return response()->json($posts);
    }

    public function evaluate(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_evaluation' => 'nullable|string|max:255',
            'category' => 'nullable|string|in:story,threat,advice,knowledge',
        ]);

        $post->status = $validated['status'];
        if (isset($validated['admin_evaluation'])) {
            $post->admin_evaluation = $validated['admin_evaluation'];
        }
        if (isset($validated['category'])) {
            $post->category = $validated['category'];
        }

        $post->save();

        return response()->json([
            'message' => 'Post evaluated successfully.',
            'post' => $post
        ]);
    }

    public function users(): JsonResponse
    {
        $users = User::withCount(['posts', 'scans'])
            ->latest()
            ->paginate(20);

        return response()->json($users);
    }

    public function deleteUser(User $user): JsonResponse
    {
        if ($user->id === 1) {
            return response()->json(['message' => 'Root admin cannot be deleted.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Operator deleted successfully.']);
    }
}
