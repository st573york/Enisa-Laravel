<?php

namespace App\Jobs;

use App\HelperFunctions\IndexConfigurationHelper;
use App\HelperFunctions\TaskHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ImportIndexProperties implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 0;

    public $year;
    public $user;
    public $excel;
    public $originalName;

    /**
     * Create a new job instance.
     */
    public function __construct($year, $user, $excel, $originalName)
    {
        Log::debug('Running import index properties job');

        $this->year = $year;
        $this->user = $user;
        $this->excel = $excel;
        $this->originalName = $originalName;
    }

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        Log::debug('Handling import index properties');

        TaskHelper::updateOrCreateTask([
            'type' => static::class,
            'user_id' => $this->user->id,
            'status_id' => 1,
            'year' => $this->year,
            'payload' => [
                'last_import_index_properties_by' => $this->user->id,
                'last_import_index_properties_at' => Carbon::now()->format('d-m-Y H:i:s')
            ]
        ]);

        // Wait for 3 seconds as job may finish instantly
        sleep(3);

        $resp = IndexConfigurationHelper::importIndexProperties($this->year, $this->excel, $this->originalName);

        if ($resp['type'] == 'error') {
            throw new Exception($resp['msg']);
        }
        
        return true;
    }
}
