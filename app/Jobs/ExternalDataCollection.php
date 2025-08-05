<?php

namespace App\Jobs;

use App\HelperFunctions\IndexDataCollectionHelper;
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

class ExternalDataCollection implements ShouldQueue, ShouldBeUnique
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
        Log::debug('Running external data collection job');

        $this->index = $index;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        Log::debug('Handling external data collection');

        TaskHelper::updateOrCreateTask([
            'type' => static::class,
            'user_id' => $this->user->id,
            'status_id' => 1,
            'index_configuration_id' => $this->index->id,
            'payload' => [
                'last_external_data_collection_by' => $this->user->id,
                'last_external_data_collection_at' => Carbon::now()->format('d-m-Y H:i:s')
            ]
        ]);

        // Wait for 3 seconds as job may finish instantly
        sleep(3);

        IndexDataCollectionHelper::discardExternalDataCollection($this->index);

        $path = app_path() . '/external-data-collection';

        $output = null;
        $retval = null;

        exec("{$path}/venv/bin/python3 {$path}/main.py", $output, $retval);

        if ($retval > 0) {
            throw new Exception(implode(', ', $output));
        }

        return $retval;
    }
}
