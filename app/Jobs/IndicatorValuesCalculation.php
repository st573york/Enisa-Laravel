<?php

namespace App\Jobs;

use App\HelperFunctions\TaskHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class IndicatorValuesCalculation implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

     /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 0;

    public $index;
    public $user;
    public $questionnaire;

    /**
     * Create a new job instance.
     */
    public function __construct($index, $user, $questionnaire)
    {
        Log::debug('Running indicator values calculation job');

        $this->index = $index;
        $this->user = $user;
        $this->questionnaire = $questionnaire;
    }

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        Log::debug('Handling indicator values calculation');

        TaskHelper::updateOrCreateTask([
            'type' => static::class,
            'user_id' => $this->user->id,
            'status_id' => 1,
            'index_configuration_id' => $this->index->id,
            'payload' => [
                'last_indicator_values_calculation_by' => $this->user->id,
                'last_indicator_values_calculation_at' => Carbon::now()->format('d-m-Y H:i:s')
            ]
        ]);

        // Wait for 3 seconds as job may finish instantly
        sleep(3);

        $retval = Artisan::call('questionnaire:calculate-scores', ['questionnaire_id' => $this->questionnaire->id]);

        if ($retval > 0) {
            throw new Exception(Artisan::output());
        }

        $retval = Artisan::call('questionnaire:calculate-values', ['questionnaire_id' => $this->questionnaire->id]);

        if ($retval > 0) {
            throw new Exception(Artisan::output());
        }

        return $retval;
    }
}
