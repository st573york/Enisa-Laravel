<?php

namespace App\Console\Commands;

use App\Jobs\IndexCalculation;
use App\Models\IndexConfiguration;
use App\Models\User;
use Illuminate\Console\Command;

class CalculateIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index:calculate {index} {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run calculation process for chosen index and user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $index = IndexConfiguration::find($this->argument('index'));
        $user = User::find($this->argument('user'));

        IndexCalculation::dispatch($index, $user);

        return 0;
    }
}
