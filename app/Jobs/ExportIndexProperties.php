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

class ExportIndexProperties implements ShouldQueue
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
    public $filename;

    public function __construct($year, $user)
    {
        Log::debug('Running export index properties job');

        $this->year = $year;
        $this->user = $user;
        $this->filename = 'Index_Properties_' . $this->year . '.xlsx';

        TaskHelper::updateOrCreateTask([
            'type' => static::class,
            'user_id' => $this->user->id,
            'status_id' => 1,
            'year' => $this->year,
            'payload' => [
                'filename' => $this->filename,
                'user' => $this->user->id,
                'year' => $this->year,
                'auditable_name' => 'Index & Survey Configuration'
            ]
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        Log::debug('Handling export index properties');

        $retval = Artisan::call('export:index-properties', [
            '--year' => $this->year,
            '--filename' => $this->filename
        ]);

        if ($retval > 0) {
            throw new Exception(Artisan::output());
        }

        return $retval;
    }
}
