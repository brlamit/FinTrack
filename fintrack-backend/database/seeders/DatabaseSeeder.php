<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
        ]);

        // User::factory(10)->create();

        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@fintrack.com',
            'role' => 'admin',
            'password_changed_at' => now(),
            'first_login_done' => true,
        ]);

        // Create regular test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_changed_at' => now(),
            'first_login_done' => true,
        ]);
    }
}
