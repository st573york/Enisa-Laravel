<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoPermissionsSeeder extends Seeder
{
    private function insertUserPermision($name, $userId, $countryId, $roleId)
    {
        DB::table('permissions')->insert([
            'name' => $name,
            'user_id' => $userId,
            'country_id' => $countryId,
            'role_id' => $roleId,
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s"),
        ]);
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $poc = DB::table('roles')
        ->where('name', '=', 'PoC')
        ->first();

        $admin = DB::table('roles')
        ->select('id')
        ->where('name', '=', 'admin')
        ->first();


        $user = User::create([
            'name' => 'Vassilis Chatzigiannakis',
            'email' => 'vchatzi@itml.gr',            
            'password' => Hash::make('password'), // password
            'remember_token' => Str::random(10),
            'blocked' => false
        ]);
        $country = DB::table('countries')
            ->select('id')
            ->where('name', '=', 'Latvia')
            ->first();
        $this->insertUserPermision('Admin', $user->id, $country->id, $admin->id);


        $user = User::create([
            'name' => 'Agathe Favetto',
            'email' => 'agathe.favetto@ssi.gouv.fr',            
            'password' => Hash::make('password'), // password
            'remember_token' => Str::random(10),
            'blocked' => 0
        ]);
        $country = DB::table('countries')
            ->select('id')
            ->where('name', '=', 'France')
            ->first();
        $this->insertUserPermision('Point of Contact', $user->id, $country->id, $poc->id);



        $user = User::create([
            'name' => 'Peter Wallstrom',
            'email' => 'peter.wallstrom@pts.se',            
            'password' => Hash::make('password'), // password
            'remember_token' => Str::random(10),
            'blocked' => false
        ]);
        $country = DB::table('countries')
            ->select('id')
            ->where('name', '=', 'Sweden')
            ->first();
        $this->insertUserPermision('Point of Contact', $user->id, $country->id, $poc->id);



        $user = User::create([
            'name' => 'Eric Romang',
            'email' => 'eric.romang@govcert.etat.lu',            
            'password' => Hash::make('password'), // password
            'remember_token' => Str::random(10),
            'blocked' => false
        ]);
        $country = DB::table('countries')
            ->select('id')
            ->where('name', '=', 'Luxembourg')
            ->first();
        $this->insertUserPermision('Point of Contact', $user->id, $country->id, $poc->id);


        $user = User::create([
            'name' => 'Arno Spiegel',
            'email' => 'arno.spiegel@bka.gv.at',            
            'password' => Hash::make('password'), // password
            'remember_token' => Str::random(10),
            'blocked' => false
        ]);
        $country = DB::table('countries')
            ->select('id')
            ->where('name', '=', 'Austria')
            ->first();
        $this->insertUserPermision('Point of Contact', $user->id, $country->id, $poc->id);


        $user = User::create([
            'name' => 'Adam Vajkovszky',
            'email' => 'adam.vajkovszky@nki.gov.hu',            
            'password' => Hash::make('password'), // password
            'remember_token' => Str::random(10),
            'blocked' => false
        ]);
        $country = DB::table('countries')
            ->select('id')
            ->where('name', '=', 'Hungary')
            ->first();
        $this->insertUserPermision('Point of Contact', $user->id, $country->id, $poc->id);


        $user = User::create([
            'name' => 'a.stamoulis',
            'email' => 'a.stamoulis@mindigital.gr',            
            'password' => Hash::make('password'), // password
            'remember_token' => Str::random(10),
            'blocked' => false
        ]);
        $country = DB::table('countries')
            ->select('id')
            ->where('name', '=', 'Greece')
            ->first();
        $this->insertUserPermision('Point of Contact', $user->id, $country->id, $poc->id);


        $user = User::create([
            'name' => 't.kellner',
            'email' => 't.kellner@nukib.cz',            
            'password' => Hash::make('password'), // password
            'remember_token' => Str::random(10),
            'blocked' => false
        ]);
        $country = DB::table('countries')
            ->select('id')
            ->where('name', '=', 'Czech Republic')
            ->first();
        $this->insertUserPermision('Point of Contact', $user->id, $country->id, $poc->id);

        

        $user = User::create([
            'name' => 's.ducci',
            'email' => 's.ducci@acn.gov.it',            
            'password' => Hash::make('password'), // password
            'remember_token' => Str::random(10),
            'blocked' => false
        ]);
        $country = DB::table('countries')
            ->select('id')
            ->where('name', '=', 'Italy')
            ->first();
        $this->insertUserPermision('Point of Contact', $user->id, $country->id, $poc->id);


        
        $user = User::create([
            'name' => 'Sanita Vitola',
            'email' => 'sanita.vitola@cert.lv',            
            'password' => Hash::make('password'), // password
            'remember_token' => Str::random(10),
            'blocked' => false
        ]);
        $country = DB::table('countries')
            ->select('id')
            ->where('name', '=', 'Latvia')
            ->first();
        $this->insertUserPermision('Point of Contact', $user->id, $country->id, $poc->id);


        $user = User::create([
            'name' => 'Christos Dimou',
            'email' => 'cdimou@itml.gr',            
            'password' => Hash::make('password'), // password
            'remember_token' => Str::random(10),
            'blocked' => false
        ]);
        $country = DB::table('countries')
            ->select('id')
            ->where('name', '=', 'Germany')
            ->first();

        $this->insertUserPermision('Point of Contact', $user->id, $country->id, $admin->id);

    }
}
