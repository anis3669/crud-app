<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = Role::where('slug', 'admin')->firstOrFail();

        $admin->permissions()->sync(
            Permission::where('is_active', true)->pluck('id')
        );

        // Manager
        $manager = Role::where('slug', 'manager')->firstOrFail();

        $managerPermissions = [
            'products.view',
            'products.create',
            'products.update',
            'products.delete',

            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',

            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',

            'inventory.view',
            'inventory.adjust',
            'inventory.history',

            'invoices.view',
            'invoices.create',
        ];

        $manager->permissions()->sync(
            Permission::whereIn('slug', $managerPermissions)->pluck('id')
        );

        // Staff
        $staff = Role::where('slug', 'staff')->firstOrFail();

        $staffPermissions = [
            'products.view',
            'inventory.view',
        ];

        $staff->permissions()->sync(
            Permission::whereIn('slug', $staffPermissions)->pluck('id')
        );
    }
}
