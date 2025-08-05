<?php

namespace Database\Seeders;

use App\Models\IndicatorQuestionType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class IndicatorQuestionTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['type' => 'single-choice'],
            ['type' => 'multiple-choice'],
            ['type' => 'free-text']
        ];
        
        foreach($types as $type) {
            IndicatorQuestionType::create([
                'type' => $type['type'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
