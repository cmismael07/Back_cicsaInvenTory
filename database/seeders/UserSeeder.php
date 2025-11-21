<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Ensure admin exists (idempotent)
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'rol' => 'Administrador',
                'activo' => true,
            ]
        );

        if (empty($admin->username)) {
            $admin->username = 'admin';
            $admin->save();
        }

        // Create additional development users. Use DEV_USER_COUNT env or default to 10.
        $desired = (int) env('DEV_USER_COUNT', 10);
        // Exclude admin from the count
        $existing = User::where('email', '!=', 'admin@example.com')->count();
        $toCreate = max(0, $desired - $existing);

        if ($toCreate > 0) {
            User::factory()->count($toCreate)->create();
        }
    }
}
