<?php

namespace App\Console\Commands;

use App\Exports\SurveyExcelExport;
use App\Models\Country;
use App\Models\Indicator;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class ExportSurveyExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:survey-excel {--y|year= : The survey year} {--c|country= : The country id (survey answers included - optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export survey excel for given year. When country id is provided, survey answers are included';

    public function validateOptions($options)
    {
        return validator(
            $options,
            [
                'year' => 'required|integer',
                'country' => [Rule::when(
                    !is_null($options['country']),
                    'integer'
                )]
            ],
            [
                'year.required' => 'The year option is required.',
                'year.integer' => 'The year option must be integer.',
                'country.integer' => 'The country option must be integer.'
            ]
        );
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ret = false;

        $year = $this->option('year');
        $country_option = $this->option('country') ? $this->option('country') : null;
        
        $path = storage_path() . '/app';
        $target = $path . '/offline-survey/' . $year;
        $filename = 'QuestionnaireTemplate.xlsx';

        File::ensureDirectoryExists($target);

        $validator = $this->validateOptions($this->options());
        if ($ret |= $validator->fails())
        {
            foreach ($validator->messages()->toArray() as $message) {
                $this->error($message[0]);
            }
        }

        if ($ret) {
            return Command::FAILURE;
        }

        $indicators = Indicator::getIndicators($year, 'survey');
        
        if (!$indicators->count())
        {
            $this->error("Survey indicators were not found for year: '{$year}'!");

            return Command::FAILURE;
        }

        $questionnaire_country = null;

        if (!is_null($country_option))
        {
            $country = Country::find($country_option);
            
            if (is_null($country))
            {
                $this->error("Country record was not found for id: '{$country_option}'!");

                return Command::FAILURE;
            }

            $published_questionnaire = Questionnaire::getExistingPublishedQuestionnaireForYear($year);

            if (is_null($published_questionnaire))
            {
                $this->error("Survey record was not found for year: '{$year}'!");

                return Command::FAILURE;
            }

            $questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $published_questionnaire->id)->where('country_id', $country->id)->first();

            $filename = 'QuestionnaireWithAnswers' . $country->iso . '.xlsx';
        }

        try
        {
            Excel::store(new SurveyExcelExport($year, $questionnaire_country), $filename);

            File::move($path . '/' . $filename, $target . '/' . $filename);
        }
        catch (\Exception $e)
        {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Survey excel successfully exported!');

        return Command::SUCCESS;
    }
}
