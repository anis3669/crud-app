<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Login
    public function apiLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 422);
        }

        $request->session()->regenerate();

        $user = Auth::user()->load('role.permissions');

        return response()->json([
            'message' => 'Login successful.',
            'user' => $this->formatUser($user),
        ]);
    }

    // Register
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $staffRole = \App\Models\Role::where('slug', 'staff')->firstOrFail();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $staffRole->id,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        $user = Auth::user()->load('role.permissions');

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $this->formatUser($user),
        ], 201);
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    // Format authenticated user
    private function formatUser(User $user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_picture' => $user->profile_picture,
            'profile_picture_url' => $user->profile_picture_url,

            'role' => $user->role
                ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'slug' => $user->role->slug,
                ]
                : null,

            'permissions' => $user->role
                ? $user->role->permissions
                ->where('is_active', true)
                ->pluck('slug')
                ->values()
                ->toArray()
                : [],
        ];
    }
}
