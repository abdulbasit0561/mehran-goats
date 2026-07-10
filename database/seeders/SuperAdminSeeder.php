<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Ensure the designated super-admin account exists with the hidden flag.
     * Idempotent — safe to run on every deploy / db:seed.
     */
    public function run(): void
    {
        $email = 'abdulbasit056@gmail.com';

        $user = User::where('email', $email)->first();

        if ($user) {
            // Ensure the flag is set even if the account pre-existed
            if (!$user->is_super_admin) {
                $user->is_super_admin = true;
                $user->save();
            }
            return;
        }

        $user = new User();
        $user->name = 'Abdul Basit';
        $user->email = $email;
        $user->password = Hash::make('Mehran@Super2026!');
        $user->is_super_admin = true;
        $user->save();
    }
}
