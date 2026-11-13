<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrNew(['email' => 'admin@telkom.test']);
        $user->name     = 'Ditrya Tryandhana';
        $user->username = 'admin';
        $user->password = Hash::make('password');
        $user->save();
    }
}