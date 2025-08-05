<?php

namespace App\Console\Commands;

use App\Exports\DataExport;
use App\HelperFunctions\DataExportHelper;
use App\Models\Country;
use App\Models\Indicator;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ExportData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:data {--year=} {--country=*} {--source=*} {--user=} {--filename=} {--index-flag=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create export data excel file for given year, countries, sources and user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $year = $this->option('year');
        $countries = $this->option('country');
        $sources = $this->option('source');
        $user = $this->option('user');
        $indexDataFlag = $this->option('index-flag');
        
        if ($countries == ['all']) {
            $countries = Country::pluck('id')->toArray();
        }
        
        if ($sources == ['all'])
        {
            $indexDataFlag = 1;
            $sources = Indicator::where('category', '!=', 'eu-wide')->distinct()->pluck('category')->toArray();
        }

        $filename = $this->option('filename') ? $this->option('filename') : DataExportHelper::createFilename($user, $year, $countries, $sources);

        try {
            Excel::store(new DataExport($countries, $sources, $year, $indexDataFlag), $filename);
        }
        catch (\Exception $e)
        {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Data exported successfully!');

        return Command::SUCCESS;
    }
}
