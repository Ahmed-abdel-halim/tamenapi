<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            [ 'username' => 'admin' ],
            [
                'name' => 'المدير',
                'email' => 'admin@admin.com',
                'password' => Hash::make('123456'),
            ]
        );
    }
}
