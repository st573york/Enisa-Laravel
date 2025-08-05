<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Country;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Country::where('name', config('constants.USER_GROUP'))->delete();

        DB::statement("ALTER TABLE countries AUTO_INCREMENT = 1;");

        $eu_countries =
            config('constants.EU_COUNTRIES') +
            [config('constants.USER_GROUP') => config('constants.USER_GROUP')];

        $eu_countries_iso =
            config('constants.EU_COUNTRIES_ISO') +
            [config('constants.USER_GROUP') => null];

        foreach($eu_countries as $code => $name) {
            Country::create([
                'name' => $name,
                'code'=> $code,
                'iso' => $eu_countries_iso[$code],
                'flag_src' => ''
            ]);
        }
    }

}
