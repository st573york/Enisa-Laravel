<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IndexConfiguration;

class UpdateIndexConfigurationJsonData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index:update-json {--i|id= : The index id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the index configuration json data for given id';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->option('id');
        $index = IndexConfiguration::find($id);

        $this->info("Json data for index: '{$index->name}' will be updated based on areas/subareas/indicators for year: '{$index->year}'.");

        if ($this->confirm('Do you wish to continue?'))
        {
            $contents = IndexConfiguration::generateIndexConfigurationTemplate($index->year);

            IndexConfiguration::find($id)->update(['json_data' => $contents]);

            $this->info('Json data was successfully updated!');
        }

        return Command::SUCCESS;
    }
}
