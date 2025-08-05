<?php

namespace Database\Seeders;

use App\HelperFunctions\QuestionnaireHelper;
use App\Models\Questionnaire;
use Illuminate\Database\Seeder;

class TestQuestionnaireUsersSeeder extends Seeder
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
            $questionnaire_data = Questionnaire::getExistingPublishedQuestionnaireForYear($year);

            QuestionnaireHelper::createQuestionnaireUsers($questionnaire_data, [2, 3, 4]);
            QuestionnaireHelper::createQuestionnaireTemplate($year);
        }
    }
}
