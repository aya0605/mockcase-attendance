<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name' => 'ユーザー１',
                'email' => 'user1@example.com',
                'password' => bcrypt('password'),
                'role' => 0, 
            ],
            [
                'name' => 'ユーザー２',
                'email' => 'user2@example.com',
                'password' => bcrypt('password'),
                'role' => 0, 
            ],
            [
                'name' => 'ユーザー３',
                'email' => 'user3@example.com',
                'password' => bcrypt('password'),
                'role' => 1, 
            ]
        ];

        foreach ($users as $user) {
            DB::table('users')->insert($user);
        }
    }
}
