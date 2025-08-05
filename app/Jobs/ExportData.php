<?php

namespace App\Jobs;

use App\HelperFunctions\DataExportHelper;
use App\HelperFunctions\TaskHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Exception;

class ExportData implements ShouldQueue
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
    public $countries;
    public $sources;
    public $indexDataFlag;
    public $filename;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public function __construct($index, $user, $countries, $sources, $indexDataFlag)
    {
        Log::debug('Running export data job');

        $this->index = $index;
        $this->user = $user;
        $this->countries = $countries;
        $this->sources = $sources;
        $this->indexDataFlag = $indexDataFlag;
        $this->filename = DataExportHelper::createFilename($this->user->id, $this->index->year, $this->countries, $this->sources);

        TaskHelper::updateOrCreateTask([
            'type' => static::class,
            'user_id' => $this->user->id,
            'status_id' => 1,
            'index_configuration_id' => $this->index->id,
            'payload' => [
                'filename' => $this->filename,
                'user' => $this->user->id,
                'index' => $this->index->id,
                'sources' => $this->sources
            ]
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        Log::debug('Handling export data');

        $retval = Artisan::call('export:data', [
            '--year' => $this->index->year,
            '--country' => $this->countries,
            '--source' => $this->sources,
            '--user' => $this->user->id,
            '--filename' => $this->filename,
            '--index-flag' => $this->indexDataFlag
        ]);

        if ($retval > 0) {
            throw new Exception(Artisan::output());
        }

        return $retval;
    }
}
