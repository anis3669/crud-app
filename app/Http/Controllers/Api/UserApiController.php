<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserApiController extends Controller
{
    // List users
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'exists:roles,slug'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::with('role')
            ->select([
                'id',
                'role_id',
                'name',
                'email',
                'profile_picture',
                'email_verified_at',
                'created_at',
                'updated_at',
            ]);

        if (!empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($validated['role'])) {
            $query->whereHas('role', function ($q) use ($validated) {
                $q->where('slug', $validated['role']);
            });
        }

        $users = $query
            ->latest()
            ->paginate($validated['per_page'] ?? 10);

        return response()->json($users);
    }

    // Show user
    public function show(User $user)
    {
        $user->load('role');

        return response()->json([
            'user' => $user,
        ]);
    }

    // Create user
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'profile_picture' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        if (!$role->is_active) {
            return response()->json([
                'message' => 'The selected role is inactive.',
            ], 422);
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role_id' => $validated['role_id'],
        ];

        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request
                ->file('profile_picture')
                ->store('profile-pictures', 'public');
        }

        $user = User::create($data);

        $user->load('role');

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user,
        ], 201);
    }

    // Update user
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => ['sometimes', 'required', 'integer', 'exists:roles,id'],
            'profile_picture' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],
            'remove_profile_picture' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('role_id', $validated)) {
            $role = Role::findOrFail($validated['role_id']);

            if (!$role->is_active) {
                return response()->json([
                    'message' => 'The selected role is inactive.',
                ], 422);
            }
        }

        if (
            isset($validated['remove_profile_picture']) &&
            $validated['remove_profile_picture'] &&
            $user->profile_picture
        ) {
            Storage::disk('public')->delete($user->profile_picture);
            $user->profile_picture = null;
        }

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $user->profile_picture = $request
                ->file('profile_picture')
                ->store('profile-pictures', 'public');
        }

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }

        if (array_key_exists('password', $validated) && $validated['password']) {
            $user->password = $validated['password'];
        }

        if (array_key_exists('role_id', $validated)) {
            $user->role_id = $validated['role_id'];
        }

        $user->save();
        $user->load('role');

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user,
        ]);
    }

    // Delete user
    public function destroy(User $user, Request $request)
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $adminRole = Role::where('slug', 'admin')->first();

        if (
            $adminRole &&
            $user->role_id === $adminRole->id
        ) {
            $adminCount = User::where('role_id', $adminRole->id)->count();

            if ($adminCount <= 1) {
                return response()->json([
                    'message' => 'The last admin account cannot be deleted.',
                ], 422);
            }
        }

        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
