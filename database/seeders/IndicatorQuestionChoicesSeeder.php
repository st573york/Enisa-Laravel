<?php

namespace Database\Seeders;

use App\Models\IndicatorQuestionChoice;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class IndicatorQuestionChoicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $choices = [
            ['text' => 'Choose answer'],
            ['text' => 'Provide your answer'],
            ['text' => 'Data not available/Not willing to share']
        ];
        
        foreach($choices as $choice) {
            IndicatorQuestionChoice::create([
                'text' => $choice['text'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
