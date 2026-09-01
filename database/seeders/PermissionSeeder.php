<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Products
            ['name' => 'View Products', 'slug' => 'products.view', 'group' => 'products', 'description' => 'View products and product details.'],
            ['name' => 'Create Products', 'slug' => 'products.create', 'group' => 'products', 'description' => 'Create new products.'],
            ['name' => 'Update Products', 'slug' => 'products.update', 'group' => 'products', 'description' => 'Update existing products.'],
            ['name' => 'Delete Products', 'slug' => 'products.delete', 'group' => 'products', 'description' => 'Delete products and manage deleted products.'],

            // Categories
            ['name' => 'View Categories', 'slug' => 'categories.view', 'group' => 'categories', 'description' => 'View product categories.'],
            ['name' => 'Create Categories', 'slug' => 'categories.create', 'group' => 'categories', 'description' => 'Create new product categories.'],
            ['name' => 'Update Categories', 'slug' => 'categories.update', 'group' => 'categories', 'description' => 'Update existing product categories.'],
            ['name' => 'Delete Categories', 'slug' => 'categories.delete', 'group' => 'categories', 'description' => 'Delete product categories.'],

            // Suppliers
            ['name' => 'View Suppliers', 'slug' => 'suppliers.view', 'group' => 'suppliers', 'description' => 'View supplier information.'],
            ['name' => 'Create Suppliers', 'slug' => 'suppliers.create', 'group' => 'suppliers', 'description' => 'Create new suppliers.'],
            ['name' => 'Update Suppliers', 'slug' => 'suppliers.update', 'group' => 'suppliers', 'description' => 'Update supplier information.'],
            ['name' => 'Delete Suppliers', 'slug' => 'suppliers.delete', 'group' => 'suppliers', 'description' => 'Delete suppliers.'],

            // Inventory
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'group' => 'inventory', 'description' => 'View inventory and stock levels.'],
            ['name' => 'Adjust Inventory', 'slug' => 'inventory.adjust', 'group' => 'inventory', 'description' => 'Adjust product stock quantities.'],
            ['name' => 'View Inventory History', 'slug' => 'inventory.history', 'group' => 'inventory', 'description' => 'View stock movement history.'],

            // Users
            ['name' => 'View Users', 'slug' => 'users.view', 'group' => 'users', 'description' => 'View system users.'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'group' => 'users', 'description' => 'Create new system users.'],
            ['name' => 'Update Users', 'slug' => 'users.update', 'group' => 'users', 'description' => 'Update system users.'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'group' => 'users', 'description' => 'Delete system users.'],

            // Roles
            ['name' => 'View Roles', 'slug' => 'roles.view', 'group' => 'roles', 'description' => 'View system roles.'],
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'group' => 'roles', 'description' => 'Create, update and assign roles and permissions.'],

            // Invoices
            ['name' => 'View Invoices', 'slug' => 'invoices.view', 'group' => 'invoices', 'description' => 'View invoices.'],
            ['name' => 'Create Invoices', 'slug' => 'invoices.create', 'group' => 'invoices', 'description' => 'Create product invoices.'],
            ['name' => 'Delete Invoices', 'slug' => 'invoices.delete', 'group' => 'invoices', 'description' => 'Delete invoices.'],

            // Settings
            ['name' => 'View Settings', 'slug' => 'settings.view', 'group' => 'settings', 'description' => 'View site settings.'],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'settings', 'description' => 'Update site settings.'],

            // Events
            ['name' => 'View Events', 'slug' => 'events.view', 'group' => 'events', 'description' => 'View upcoming events.'],
            ['name' => 'Manage Events', 'slug' => 'events.manage', 'group' => 'events', 'description' => 'Create, update and delete events.'],

            // Activity
            ['name' => 'View Activity Logs', 'slug' => 'activity.view', 'group' => 'activity', 'description' => 'View system activity and audit logs.'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'group' => $permission['group'],
                    'description' => $permission['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
