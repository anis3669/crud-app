<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::where('slug', 'admin')->firstOrFail();
        $manager = Role::where('slug', 'manager')->firstOrFail();
        $staff = Role::where('slug', 'staff')->firstOrFail();

        User::factory()
            ->withRole($admin)
            ->create([
                'name' => 'Admin User',
                'email' => 'admin@gmail.com',
            ]);

        User::factory()
            ->withRole($manager)
            ->count(2)
            ->create();

        User::factory()
            ->withRole($staff)
            ->count(5)
            ->create();
    }
}
