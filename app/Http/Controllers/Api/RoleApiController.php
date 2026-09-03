<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleApiController extends Controller
{
    // List roles
    public function index()
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->latest()
            ->get();

        return response()->json([
            'roles' => $roles,
        ]);
    }

    // Show role
    public function show(Role $role)
    {
        $role->load('permissions');
        $role->loadCount('users');

        return response()->json([
            'role' => $role,
        ]);
    }

    // Create role
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                'unique:roles,slug',
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'integer',
                'exists:permissions,id',
            ],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        $role->load('permissions');

        return response()->json([
            'message' => 'Role created successfully.',
            'role' => $role,
        ], 201);
    }

    // Update role
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('roles', 'slug')->ignore($role->id),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'integer',
                'exists:permissions,id',
            ],
        ]);

        if (array_key_exists('name', $validated)) {
            $role->name = $validated['name'];
        }

        if (array_key_exists('slug', $validated)) {
            $role->slug = $validated['slug'];
        }

        if (array_key_exists('description', $validated)) {
            $role->description = $validated['description'];
        }

        if (array_key_exists('is_active', $validated)) {
            $role->is_active = $validated['is_active'];
        }

        $role->save();

        if (array_key_exists('permissions', $validated)) {
            $role->permissions()->sync($validated['permissions']);
        }

        $role->load('permissions');

        return response()->json([
            'message' => 'Role updated successfully.',
            'role' => $role,
        ]);
    }

    // Delete role
    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'This role cannot be deleted because it is assigned to users.',
            ], 422);
        }

        $role->permissions()->detach();
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }

    // List permissions
    public function permissions()
    {
        $permissions = Permission::query()
            ->where('is_active', true)
            ->orderBy('group')
            ->orderBy('name')
            ->get();

        return response()->json([
            'permissions' => $permissions,
        ]);
    }
}
