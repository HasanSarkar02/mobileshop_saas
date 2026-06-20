<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // password auto-hash হবে User model এর 'hashed' cast এর কারণে
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => '111111',
                'shop_id' => null,
                'user_type' => UserType::SuperAdmin,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}