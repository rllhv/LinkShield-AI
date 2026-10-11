<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $totalScans = $user->scans()->count();
        $totalPosts = $user->posts()->count();
        $approvedPosts = $user->posts()->where('status', 'approved')->count();
        $pendingPosts = $user->posts()->where('status', 'pending')->count();
        $rejectedPosts = $user->posts()->where('status', 'rejected')->count();

        return response()->json([
            'totalScans' => $totalScans,
            'totalPosts' => $totalPosts,
            'approvedPosts' => $approvedPosts,
            'pendingPosts' => $pendingPosts,
            'rejectedPosts' => $rejectedPosts,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.']
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json([
            'message' => 'Password updated successfully.'
        ]);
    }
}
