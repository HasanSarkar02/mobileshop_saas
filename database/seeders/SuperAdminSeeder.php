<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'hasansarkar6343@gmail.com'],
            [
                'name' => 'HASAN SARKAR',
                'password' => 'Hasan1122@',
                'shop_id' => null,
                'user_type' => UserType::SuperAdmin,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}