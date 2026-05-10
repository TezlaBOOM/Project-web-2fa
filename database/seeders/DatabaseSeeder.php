<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Moderator',
            'email' => 'mod@admin.com',
            'password' => Hash::make('mod'),
            'role' => 'mod',
        ]);

        User::create([
            'name' => 'User',
            'email' => 'user@admin.com',
            'password' => Hash::make('user'),
            'role' => 'user',
        ]);
    }
}
