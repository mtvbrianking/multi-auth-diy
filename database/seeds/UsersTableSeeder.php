<?php

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
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
