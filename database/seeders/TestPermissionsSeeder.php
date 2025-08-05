<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestPermissionsSeeder extends Seeder
{
    private function insertUserPermision($user, $country, $role)
    {
        Permission::create([
            'name' => $role->name,
            'user_id' => $user->id,
            'country_id' => $country->id,
            'role_id' => $role->id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $enisa = Country::where('name', config('constants.USER_GROUP'))->first();
        $austria = Country::where('name', 'Austria')->first();

        $admin = Role::where('name', 'admin')->first();
        $ppoc = Role::where('name', 'Primary PoC')->first();
        $poc = Role::where('name', 'PoC')->first();
        $operator = Role::where('name', 'operator')->first();

        $enisaAdmin = User::where('email', '007@mi6.eu')->first();
        $this->insertUserPermision($enisaAdmin, $enisa, $admin);

        $austriaPPoC = User::where('email', 'Jason.BOURNE@ec.europa.eu')->first();
        $this->insertUserPermision($austriaPPoC, $austria, $ppoc);

        $austriaPoC = User::where('email', 'Jack.Bauer@ctu.eu')->first();
        $this->insertUserPermision($austriaPoC, $austria, $poc);

        $austriaOperator = User::where('email', 'texasranger@chuck.norris.com.eu')->first();
        $this->insertUserPermision($austriaOperator, $austria, $operator);
    }
}
