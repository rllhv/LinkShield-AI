<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Post::with('user:id,name')
            ->where('status', 'approved')
            ->latest();

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', $searchTerm)
                  ->orWhere('content', 'like', $searchTerm)
                  ->orWhere('category', 'like', $searchTerm)
                  ->orWhere('link', 'like', $searchTerm);
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('evaluation')) {
            if ($request->evaluation === 'unevaluated') {
                $query->whereNull('admin_evaluation');
            } elseif ($request->evaluation === 'evaluated') {
                $query->whereNotNull('admin_evaluation');
            }
        }

        $paginator = $query->paginate(15);
            
        $paginator->getCollection()->transform(function ($post) {
            if ($post->is_anonymous) {
                // Privacy: completely mask the user details in the backend response
                $post->unsetRelation('user');
                $post->makeHidden('user_id');
                $post->user = (object)['name' => 'Anonymous Operator'];
            }
            if ($post->image_path) {
                $post->image_url = asset('storage/' . $post->image_path);
            }
            return $post;
        });

        return response()->json($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'content'      => 'required|string',
            'category'     => 'required|string|in:story,threat,advice,knowledge',
            'link'         => 'nullable|url|max:2048',
            'is_anonymous' => 'nullable|boolean',
            'image'        => 'nullable|image|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts', 'public');
        }

        $post = $request->user()->posts()->create([
            'title'        => $validated['title'],
            'content'      => $validated['content'],
            'category'     => $validated['category'],
            'link'         => $validated['link'] ?? null,
            'is_anonymous' => $request->boolean('is_anonymous'),
            'image_path'   => $imagePath,
            'status'       => 'pending',
        ]);

        return response()->json([
            'message' => 'Post submitted successfully and is awaiting admin approval.',
            'post'    => $post,
        ], 201);
    }

    public function myPosts(Request $request): JsonResponse
    {
        $paginator = $request->user()->posts()->latest()->paginate(20);
        $paginator->getCollection()->transform(function ($post) {
            if ($post->image_path) {
                $post->image_url = asset('storage/' . $post->image_path);
            }
            return $post;
        });
        return response()->json($paginator);
    }
}
