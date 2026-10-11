<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Check if root admin exists, if not create
        if (!User::find(1)) {
            $email    = env('ADMIN_DEFAULT_EMAIL', 'admin@example.com');
            $password = env('ADMIN_DEFAULT_PASSWORD');

            // Never fall back to a known default password.
            if (empty($password)) {
                throw new \RuntimeException(
                    'ADMIN_DEFAULT_PASSWORD is not set. Add a strong password to your .env file before seeding.'
                );
            }

            User::create([
                'id'       => 1,
                'name'     => 'Root Admin',
                'email'    => $email,
                'password' => Hash::make($password),
                'role'     => 'admin',
            ]);
        }
    }
}
