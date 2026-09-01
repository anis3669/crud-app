<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        | Full access to the system.
        */
        $admin = Role::where('slug', 'admin')->firstOrFail();

        $admin->permissions()->sync(
            Permission::where('is_active', true)->pluck('id')
        );

        /*
        |--------------------------------------------------------------------------
        | Manager
        |--------------------------------------------------------------------------
        | Can manage products, categories, suppliers, inventory,
        | invoices and events, but cannot manage users, roles,
        | permissions or site settings.
        */
        $manager = Role::where('slug', 'manager')->firstOrFail();

        $managerPermissions = [
            // Products
            'products.view',
            'products.create',
            'products.update',
            'products.delete',

            // Categories
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',

            // Suppliers
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',

            // Inventory
            'inventory.view',
            'inventory.adjust',
            'inventory.history',

            // Invoices
            'invoices.view',
            'invoices.create',
            'invoices.delete',

            // Events
            'events.view',
            'events.manage',

            // Activity
            'activity.view',
        ];

        $manager->permissions()->sync(
            Permission::whereIn('slug', $managerPermissions)->pluck('id')
        );

        /*
        |--------------------------------------------------------------------------
        | Staff
        |--------------------------------------------------------------------------
        | Operational access only.
        */
        $staff = Role::where('slug', 'staff')->firstOrFail();

        $staffPermissions = [
            // Products
            'products.view',

            // Categories
            'categories.view',

            // Suppliers
            'suppliers.view',

            // Inventory
            'inventory.view',
            'inventory.history',

            // Invoices
            'invoices.view',
            'invoices.create',

            // Events
            'events.view',
        ];

        $staff->permissions()->sync(
            Permission::whereIn('slug', $staffPermissions)->pluck('id')
        );
    }
}
