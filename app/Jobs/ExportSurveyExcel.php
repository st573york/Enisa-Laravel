<?php

namespace App\Jobs;

use App\HelperFunctions\QuestionnaireCountryHelper;
use App\HelperFunctions\QuestionnaireHelper;
use App\HelperFunctions\TaskHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ExportSurveyExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 0;

    public $user;
    public $questionnaire;
    public $year;
    public $indicators;
    public $country;
    public $type;
    public $filename_to_read;
    public $filename_to_download;

    /**
     * Create a new job instance.
     */
    public function __construct($user, $questionnaire, $year, $indicators, $country, $type)
    {
        Log::debug('Running export survey excel');

        $this->user = $user;
        $this->questionnaire = $questionnaire;
        $this->year = $year;
        $this->indicators = $indicators;
        $this->country = $country;
        $this->type = $type;
        $this->filename_to_read = 'Questionnaire';
        $this->filename_to_download = '';

        if ($this->type == 'survey_template') {
            $this->filename_to_read .= 'Template';
        }
        elseif ($this->type == 'survey_with_answers') {
            $this->filename_to_read .= 'WithAnswers' . $this->country->iso;
        }

        $filename_extension = '.xlsx';

        $this->filename_to_download .= $this->filename_to_read . $this->questionnaire->year . $filename_extension;
        $this->filename_to_read .= $filename_extension;

        $payload = [
            'filename' => $this->filename_to_download,
            'user' => $this->user->id
        ];
        if (!is_null($this->questionnaire)) {
            $payload['questionnaire'] = $this->questionnaire->id;
        }
        else
        {
            $payload['year'] = $this->year;
            $payload['auditable_name'] = 'Index & Survey Configuration';
        }

        TaskHelper::updateOrCreateTask([
            'type' => static::class,
            'user_id' => $this->user->id,
            'status_id' => 1,
            'index_configuration_id' => $this->questionnaire->configuration->id ?? null,
            'year' => $this->year,
            'payload' => $payload
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        Log::debug('Handling export survey excel');

        if ($this->type == 'survey_template')
        {
            if (!File::exists(storage_path() . '/app/offline-survey/' . $this->year . '/' . $this->filename_to_read) ||
                is_null($this->questionnaire))
            {
                QuestionnaireHelper::createQuestionnaireTemplate($this->year);
            }
        }
        elseif ($this->type == 'survey_with_answers') {
            QuestionnaireHelper::createQuestionnaireWithAnswers($this->year, $this->country);
        }
        
        QuestionnaireCountryHelper::downloadExcel(
            $this->user,
            $this->year,
            $this->indicators,
            $this->type,
            $this->filename_to_read,
            $this->filename_to_download
        );

        return true;
    }
}
