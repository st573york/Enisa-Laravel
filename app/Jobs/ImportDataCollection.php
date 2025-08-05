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

class ImportDataCollection implements ShouldQueue, ShouldBeUnique
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
    public $excel;

    /**
     * Create a new job instance.
     */
    public function __construct($index, $user, $excel)
    {
        Log::debug('Running import data collection job');

        $this->index = $index;
        $this->user = $user;
        $this->excel = $excel;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): bool
    {
        Log::debug('Handling import data collection');

        TaskHelper::updateOrCreateTask([
            'type' => static::class,
            'user_id' => $this->user->id,
            'status_id' => 1,
            'index_configuration_id' => $this->index->id,
            'payload' => [
                'last_import_data_collection_by' => $this->user->id,
                'last_import_data_collection_at' => Carbon::now()->format('d-m-Y H:i:s')
            ]
        ]);

        // Wait for 3 seconds as job may finish instantly
        sleep(3);

        $resp = IndexDataCollectionHelper::storeImportDataCollection($this->excel, $this->index);

        if ($resp['type'] == 'error') {
            throw new Exception($resp['msg']);
        }

        return true;
    }
}
