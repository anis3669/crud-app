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
            'profile_picture_url' => $user->profile_picture_url,
        ]);
    }

    /**
     * Update personal details.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($user->id),
            ],
        ]);

        $user->update($validated);

        $user = $user->fresh();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user,
            'profile_picture_url' => $user->profile_picture_url,
        ]);
    }

    /**
     * Upload or replace profile picture.
     */
    public function uploadPicture(Request $request)
    {
        $validated = $request->validate([
            'profile_picture' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $user = $request->user();

        /*
         * Delete the old picture first.
         */
        if ($user->profile_picture) {
            Storage::disk('public')->delete(
                $user->profile_picture
            );
        }

        /*
         * Store the new picture.
         */
        $path = $validated['profile_picture']
            ->store('profile-pictures', 'public');

        /*
         * Save path in database.
         */
        $user->update([
            'profile_picture' => $path,
        ]);

        $user = $user->fresh();

        return response()->json([
            'message' => 'Profile picture updated successfully.',
            'user' => $user,
            'profile_picture_url' => $user->profile_picture_url,
        ]);
    }

    /**
     * Remove profile picture.
     */
    public function deletePicture(Request $request)
    {
        $user = $request->user();

        /*
         * Delete physical file.
         */
        if ($user->profile_picture) {
            Storage::disk('public')->delete(
                $user->profile_picture
            );
        }

        /*
         * Remove database path.
         */
        $user->update([
            'profile_picture' => null,
        ]);

        $user = $user->fresh();

        return response()->json([
            'message' => 'Profile picture removed successfully.',
            'user' => $user,
            'profile_picture_url' => null,
        ]);
    }
}
