<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        User::updateOrCreate(
            ['email' => 'admin@oweru.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // Create Moderator User
        User::updateOrCreate(
            ['email' => 'moderator@oweru.com'],
            [
                'name' => 'Moderator User',
                'password' => Hash::make('moderator123'),
                'role' => 'moderator',
            ]
        );

        $this->command->info('Default users created successfully!');
        $this->command->info('Admin: admin@oweru.com / admin123');
        $this->command->info('Moderator: moderator@oweru.com / moderator123');
    }
}

