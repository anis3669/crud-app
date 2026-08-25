<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create login user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'anisbastola@gmail.com',
            'password' => 'password',
        ]);

        // Create random products
        $this->call([
            ProductSeeder::class,
        ]);
    }
}
