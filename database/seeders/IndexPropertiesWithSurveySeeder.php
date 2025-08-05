<?php

namespace Database\Seeders;

use App\HelperFunctions\IndexConfigurationHelper;
use Illuminate\Database\Seeder;

class IndexPropertiesWithSurveySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $years = (getenv('YEARS_SEEDER') !== false) ? preg_split('/ |, |,/', env('YEARS_SEEDER')) : config('constants.LAST_2_YEARS');

        foreach($years as $year)
        {
            $filename = 'Index-Properties-With-Survey.xlsx';
            $file = __DIR__ . '/Importers/import-files/' . $year . '/' . $filename;

            if (file_exists($file)) {
                IndexConfigurationHelper::importIndexProperties($year, $file, $filename, true);
            }
        }
    }
}
