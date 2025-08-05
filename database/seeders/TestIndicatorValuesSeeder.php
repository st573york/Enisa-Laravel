<?php

namespace Database\Seeders;

use App\HelperFunctions\IndexDataCollectionHelper;
use App\Models\Country;
use App\Models\IndexConfiguration;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use Illuminate\Database\Seeder;

class TestIndicatorValuesSeeder extends Seeder
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
            $path = __DIR__ . '/Importers/import-files/' . $year;
            $file = $path . '/index-calculation/files/CSIndData.json';

            if (file_exists($file))
            {
                $data = json_decode(file_get_contents($file), true);
                $indicators = array_slice($data, 2);
                $countries = Country::whereIn('code', $data['UnitCode'])->pluck('id')->toArray();
                
                foreach ($indicators as $indicatorKey => $indicatorData)
                {
                    $parts = explode('IND_', $indicatorKey);
                    $indicator = Indicator::where('identifier', $parts[1])->where('year', $year)->first();
                    
                    if (!is_null($indicator))
                    {
                        foreach ($indicatorData as $key => $val)
                        {
                            if (is_numeric($val)) {
                                IndicatorValue::updateOrCreateIndicatorValue([
                                    'indicator_id' => $indicator->id,
                                    'country_id' => $countries[$key],
                                    'year' => $year,
                                    'value' => $val
                                ]);
                            }
                        }
                    }
                }
            }

            $file = $path . '/import-indicators.xlsx';
            $index = IndexConfiguration::getExistingPublishedConfigurationForYear($year);

            if (file_exists($file)) {
                IndexDataCollectionHelper::storeImportDataCollection($file, $index);
            }
        }
    }
}