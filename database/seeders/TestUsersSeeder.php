<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => '007',
            'email' => '007@mi6.eu',
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'blocked' => false
        ]);

        User::create([
            'name' => 'Jason BOURNE',
            'email' => 'Jason.BOURNE@ec.europa.eu',
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'blocked' => false
        ]);

        User::create([
            'name' => 'Jack BAUER',
            'email' => 'Jack.Bauer@ctu.eu',
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'blocked' => false
        ]);

        User::create([
            'name' => 'Chuck NORRIS',
            'email' => 'texasranger@chuck.norris.com.eu',
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'blocked' => false
        ]);
    }
}
