<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Owner',
            'username' => 'owner',
            'password' => bcrypt('owner123'),
            'role' => 'owner',
            'is_active' => true,
        ]);
        User::create([
            'name' => 'Ibad',
            'username' => 'cashier',
            'password' => bcrypt('cashier123'),
            'role' => 'cashier',
            'is_active' => true,
        ]);
    }
}
