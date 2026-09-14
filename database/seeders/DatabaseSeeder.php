<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
        ]);

        Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Full system access.',
                'is_active' => true,
            ]
        );

        Role::updateOrCreate(
            ['slug' => 'manager'],
            [
                'name' => 'Manager',
                'description' => 'Manage products, inventory, suppliers, categories and invoices.',
                'is_active' => true,
            ]
        );

        Role::updateOrCreate(
            ['slug' => 'staff'],
            [
                'name' => 'Staff',
                'description' => 'Operational access to products and inventory.',
                'is_active' => true,
            ]
        );

        $this->call([
            UserSeeder::class,
            RolePermissionSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
