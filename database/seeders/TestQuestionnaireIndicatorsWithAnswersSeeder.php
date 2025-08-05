<?php

namespace Database\Seeders;

use App\Models\Indicator;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class TestQuestionnaireIndicatorsWithAnswersSeeder extends Seeder
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
            $indicators = Indicator::where('category', 'survey')->where('year', $year)->orderBy('identifier')->get();
        
            foreach ($indicators as $indicator)
            {
                $configuration_json = $indicator->configuration_json;
            
                foreach ($configuration_json['form'] as &$accordions)
                {
                    foreach ($accordions['contents'] as &$question)
                    {
                        $question['id'] = $indicator->id;
                        $question['accordion'] = $accordions['order'];
                        $question['inputs'] = [
                            'choice' => 'choice',
                            'answers' => 'answers',
                            'reference' => 'reference',
                            'rating' => 'rating'
                        ];

                        if ($question['type'] == 'multiple-choice') {
                            $question['answers'] = [
                                '1',
                                '2',
                                '3'
                            ];
                        }
                        elseif ($question['type'] == 'single-choice') {
                            $question['answers'] = [
                                '1'
                            ];
                        }
                        elseif ($question['type'] == 'free-text') {
                            $question['answers'] = [
                                (isset($question['validation']['answers']['type']) && $question['validation']['answers']['type'] == 'numeric') ? rand(0, 100) : Str::random(10)
                            ];
                        }

                        $question['compatible'] = true;
                        $question['reference'] = Str::random(10);
                        $question['comments'] = Str::random(10);
                        $question['rating'] = 1;
                        $question['comments_loaded'] = false;
                        $question['rating_loaded'] = false;
                        $question['last_saved'] = date('d-m-Y H:i:s');
                    }
                }
            
                $indicator->configuration_json = $configuration_json;
                $indicator->save();
            }
        }
    }
}
