<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'owner@finance.com'],
            [
                'name' => 'Admin Owner',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'incharge@finance.com'],
            [
                'name' => 'Branch Incharge',
                'password' => Hash::make('password'),
                'role' => 'incharge',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'cashier@finance.com'],
            [
                'name' => 'Shift Cashier',
                'password' => Hash::make('password'),
                'role' => 'cashier',
                'status' => 'active',
            ]
        );

    }
}
