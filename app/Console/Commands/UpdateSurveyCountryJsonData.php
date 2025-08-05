<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

class UpdateSurveyCountryJsonData extends Command
{
    protected $rules;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'survey:update-country-json {--s|survey= : The survey id} {--c|country= : The country id} {--f|file= : The json data that will overwrite the existing one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the survey country json data for given survey, country and json file';

    public function validateOptions($options)
    {
        return validator(
            $options,
            [
                'survey' => 'required|integer',
                'country' => 'required|integer',
                'file' => 'required'
            ],
            [
                'survey.required' => 'The survey option is required.',
                'survey.integer' => 'The survey option must be integer.',
                'country.required' => 'The country option is required.',
                'country.integer' => 'The country option must be integer.',
                'file.required' => 'The file option is required.'
            ]
        );
    }

    public function validateFile($file)
    {
        if (!File::exists($file))
        {
            $this->error('File does not exist!');

            return true;
        }

        if (File::extension($file) != 'json')
        {
            $this->error('File must have a .json extension!');

            return true;
        }

        return false;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ret = false;

        $questionnaire_option = $this->option('survey');
        $country_option = $this->option('country');
        $file = $this->option('file');
        
        $validator = $this->validateOptions($this->options());
        if ($ret |= $validator->fails())
        {
            foreach ($validator->messages()->toArray() as $message) {
                $this->error($message[0]);
            }
        }
        
        $ret |= $this->validateFile($file);
        
        if ($ret) {
            return Command::FAILURE;
        }
        
        $questionnaire = Questionnaire::find($questionnaire_option);

        if (is_null($questionnaire))
        {
            $this->error("Survey record was not found for survey id: '{$questionnaire_option}'!");

            return Command::FAILURE;
        }
        
        $country = Country::find($country_option);
        
        if (is_null($country))
        {
            $this->error("Country record was not found for country id: '{$country_option}'!");

            return Command::FAILURE;
        }
        
        $this->info("Json data for survey: '{$questionnaire->title}' and country: '{$country->name}' will be updated.");

        if ($this->confirm('Do you wish to continue?'))
        {
            $contents = File::get($file);

            $json_data = json_decode($contents, true);
        
            QuestionnaireCountry::where('questionnaire_id', $questionnaire->id)->where('country_id', $country->id)->update(['json_data' => $json_data]);

            $this->info('Json data was successfully updated!');

            return Command::SUCCESS;
        }
    }
}
