<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $user = new User();
        $user->name = 'jdoe';
        $user->email = 'jdoe@example.com';
        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->password = Hash::make('123456');
        $user->save();
    }
}
