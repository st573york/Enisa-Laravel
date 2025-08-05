<?php

namespace App\Console\Commands;

use App\Exports\IndexPropertiesExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ExportIndexProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:index-properties {--y|year= : The index properties year} {--f|filename= : The filename that will be exported (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export index properties for given year and filename';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->option('year');
        $filename = $this->option('filename') ? $this->option('filename') : 'Index_Properties_' . $year . '.xlsx';

        try {
            Excel::store(new IndexPropertiesExport($year), $filename);
        }
        catch (\Exception $e)
        {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Index properties successfully exported!');

        return Command::SUCCESS;
    }
}
