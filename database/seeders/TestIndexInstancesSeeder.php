<?php

namespace Database\Seeders;

use App\HelperFunctions\IndexCalculationHelper;
use App\Models\IndexConfiguration;
use Illuminate\Database\Seeder;

class TestIndexInstancesSeeder extends Seeder
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
            $index_data = IndexConfiguration::getExistingPublishedConfigurationForYear($year);

            $path = __DIR__ . '/Importers/import-files/' . $year . '/index-calculation';

            IndexCalculationHelper::importCalculationData($index_data, $path);
        }
    }
}