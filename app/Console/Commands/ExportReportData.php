<?php

namespace App\Console\Commands;

use App\Exports\ReportDataExport;
use App\Models\BaselineIndex;
use App\Models\Country;
use App\Models\Index;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;

class ExportReportData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:report-data {--y|year= : The report data year} {--c|country= : The report data country id (optional - leave empty for EU report data)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create export report data excel file for given year and country';

    public function validateOptions($options)
    {
        return validator(
            $options,
            [
                'year' => 'required|integer',
                'country' => [Rule::when(
                    !is_null($options['country']),
                    'integer'
                )]
            ],
            [
                'year.required' => 'The year option is required.',
                'year.integer' => 'The year option must be integer.',
                'country.integer' => 'The country option must be integer.'
            ]
        );
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $ret = false;

        $year = $this->option('year');
        $country_option = $this->option('country');

        $validator = $this->validateOptions($this->options());
        if ($ret |= $validator->fails())
        {
            foreach ($validator->messages()->toArray() as $message) {
                $this->error($message[0]);
            }
        }

        if ($ret) {
            return Command::FAILURE;
        }

        $index = Index::with('configuration')->whereHas('configuration', function ($query) use ($year) {
            $query->where('year', $year);
        })->first();

        if (is_null($index))
        {
            $this->error("Report data record was not found for year: '{$year}'!");

            return Command::FAILURE;
        }

        if (!is_null($country_option))
        {
            $country = Country::find($country_option);
            
            if (is_null($country))
            {
                $this->error("Country record was not found for id: '{$country_option}'!");

                return Command::FAILURE;
            }

            $index = Index::where('country_id', $country->id)->where('index_configuration_id', $index->configuration->id)->first();

            $data = $index->report_json[0];
            $filename = 'EUCSI-MS-report-' . $year . '-' . $country->iso . '.xlsx';
        }
        else
        {
            $baseline_index = BaselineIndex::where('index_configuration_id', $index->configuration->id)->first();

            $data = $baseline_index->report_json[0];
            $filename = 'EUCSI-EU-report-' . $year . '.xlsx';
        }
                
        try {
            Excel::store(new ReportDataExport($country_option, $data), $filename);
        }
        catch (\Exception $e)
        {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Report data exported successfully!');

        return Command::SUCCESS;
    }
}
