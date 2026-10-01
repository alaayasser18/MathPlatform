<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::firstOrCreate(
            [
                'email' => 'yasseralaa041@gmail.com',
            ],
            [
                'name' => 'Alaa-Admin',
                'password' => 'Admin@18',
                'is_active' => true,
            ]
        );
    }
}