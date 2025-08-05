<?php

namespace App\Jobs;

use App\HelperFunctions\IndexCalculationHelper;
use App\HelperFunctions\TaskHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class IndexCalculation implements ShouldQueue, ShouldBeUnique
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

    /**
     * Create a new job instance.
     */
    public function __construct($index, $user)
    {
        Log::debug('Running index calculation job');

        $this->index = $index;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        Log::debug('Handling index calculation');

        TaskHelper::updateOrCreateTask([
            'type' => static::class,
            'user_id' => $this->user->id,
            'status_id' => 1,
            'index_configuration_id' => $this->index->id,
            'payload' => [
                'last_index_calculation_by' => $this->user->id,
                'last_index_calculation_at' => Carbon::now()->format('d-m-Y H:i:s')
            ]
        ]);

        // Wait for 3 seconds as job may finish instantly
        sleep(3);

        $result = IndexCalculationHelper::calculateIndex($this->index);

        if ($result['retval'] > 0) {
            throw new Exception($result['output']);
        }

        return $result['retval'];
    }
}
