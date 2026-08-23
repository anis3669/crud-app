<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => $user,
            'profile_picture_url' => $user->profile_picture
                ? asset('storage/' . $user->profile_picture)
                : null,
        ]);
    }

    /**
     * Update personal details.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh(),
            'profile_picture_url' => $user->profile_picture
                ? asset('storage/' . $user->profile_picture)
                : null,
        ]);
    }

    /**
     * Upload profile picture.
     */
    public function uploadPicture(Request $request)
    {
        $request->validate([
            'profile_picture' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $user = $request->user();

        // Delete old picture
        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        // Store new picture
        $path = $request->file('profile_picture')
            ->store('profile-pictures', 'public');

        $user->update([
            'profile_picture' => $path,
        ]);

        return response()->json([
            'message' => 'Profile picture updated successfully.',
            'user' => $user->fresh(),
            'profile_picture_url' => asset('storage/' . $path),
        ]);
    }

    /**
     * Remove profile picture.
     */
    public function deletePicture(Request $request)
    {
        $user = $request->user();

        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);

            $user->update([
                'profile_picture' => null,
            ]);
        }

        return response()->json([
            'message' => 'Profile picture removed successfully.',
            'user' => $user->fresh(),
            'profile_picture_url' => null,
        ]);
    }
}