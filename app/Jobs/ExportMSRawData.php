<?php

namespace App\Jobs;

use App\HelperFunctions\TaskHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Exception;

class ExportMSRawData implements ShouldQueue
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
    public $country;

    public function __construct($year, $user, $country)
    {
        Log::debug('Running export ms raw data job');

        $this->year = $year;
        $this->user = $user;
        $this->country = $country;

        $filename = 'EUCSI-MS-raw-data-' . $this->year . '-' . $this->country->iso . '.xlsx';
        
        TaskHelper::updateOrCreateTask([
            'type' => static::class,
            'user_id' => $this->user->id,
            'status_id' => 1,
            'year' => $this->year,
            'payload' => [
                'filename' => $filename,
                'user' => $this->user->id,
                'year' => $this->year,
                'auditable_name' => 'MS Raw Data'
            ]
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        Log::debug('Handling export ms raw data');

        $retval = Artisan::call('export:ms-raw-data', [
            '--year' => $this->year,
            '--country' => $this->country->id
        ]);

        if ($retval > 0) {
            throw new Exception(Artisan::output());
        }

        return $retval;
    }
}
