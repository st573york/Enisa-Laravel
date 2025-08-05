<?php

namespace App\Jobs;

use App\HelperFunctions\TaskHelper;
use App\Models\Country;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Exception;

class ExportReportData implements ShouldQueue
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
        Log::debug('Running export report data job');

        $this->year = $year;
        $this->user = $user;
        $this->country = $country;

        if (!is_null($this->country))
        {
            $country = Country::find($this->country);

            $filename = 'EUCSI-MS-report-' . $this->year . '-' . $country->iso . '.xlsx';
        }
        else {
            $filename = 'EUCSI-EU-report-' . $this->year . '.xlsx';
        }
        
        TaskHelper::updateOrCreateTask([
            'type' => static::class,
            'user_id' => $this->user->id,
            'status_id' => 1,
            'year' => $this->year,
            'payload' => [
                'filename' => $filename,
                'user' => $this->user->id,
                'year' => $this->year,
                'auditable_name' => 'Report Data'
            ]
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        Log::debug('Handling export report data');

        $args = ['--year' => $this->year];
        if (!is_null($this->country)) {
            $args['--country'] = $this->country;
        }

        $retval = Artisan::call('export:report-data', $args);

        if ($retval > 0) {
            throw new Exception(Artisan::output());
        }

        return $retval;
    }
}
