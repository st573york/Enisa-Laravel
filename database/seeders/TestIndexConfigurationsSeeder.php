<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\IndexConfiguration;

class TestIndexConfigurationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $years = (getenv('YEARS_SEEDER') !== false) ? preg_split('/ |, |,/', env('YEARS_SEEDER')) : config('constants.LAST_2_YEARS');
        
        foreach($years as $year)
        {
            Artisan::call('index:create',
                [
                    '-N' => "Test Index {$year}",
                    '-D' => "This is the test index configuration for {$year}",
                    '-Y' => $year
                ]
            );

            $user = User::with('permissions')->whereHas('permissions', function ($query) {
                $query->where('role_id', 1);
            })->first();

            $published_index = IndexConfiguration::getExistingPublishedConfigurationForYear($year);

            IndexConfiguration::create([
                'name' => 'Test Draft Index ' . $year,
                'description' => 'This is the test draft index configuration for ' . $year,
                'year' => $year,
                'draft' => true,
                'user_id' => $user->id,
                'json_data' => (!is_null($published_index)) ? $published_index->json_data : '{}'
            ]);
        }
    }
}
