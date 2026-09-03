<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions first
        $this->call([
            PermissionSeeder::class,
        ]);

        // Create roles
        $admin = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Full system access.',
                'is_active' => true,
            ]
        );

        $manager = Role::updateOrCreate(
            ['slug' => 'manager'],
            [
                'name' => 'Manager',
                'description' => 'Manage products, inventory, suppliers, categories and invoices.',
                'is_active' => true,
            ]
        );

        $staff = Role::updateOrCreate(
            ['slug' => 'staff'],
            [
                'name' => 'Staff',
                'description' => 'Operational access to products and inventory.',
                'is_active' => true,
            ]
        );

        // Create login user as Admin
        User::updateOrCreate(
            [
                'email' => 'anisbastola@gmail.com',
            ],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'role_id' => $admin->id,
            ]
        );

        // Seed role permissions
        $this->call([
            RolePermissionSeeder::class,
        ]);

        // Create products
        $this->call([
            ProductSeeder::class,
        ]);

        // Create suppliers
        $this->call([
            SupplierSeeder::class,
        ]);
    }
}
