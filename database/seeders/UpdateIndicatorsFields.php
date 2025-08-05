<?php

namespace Database\Seeders;

use App\Models\Indicator;
use Illuminate\Database\Seeder;

class UpdateIndicatorsFields extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $years = config('constants.YEARS_TO_DATE');
        
        // Indicators
        foreach($years as $year)
        {
            $indicators = Indicator::where('category', 'survey')->where('year', $year)->orderBy('identifier')->get();

            foreach ($indicators as $key => $indicator)
            {
                $indicator->order = $key + 1;
                $indicator->validated = true;
                $indicator->save();
            }
        }
    }
}
