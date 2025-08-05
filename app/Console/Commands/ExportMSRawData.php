<?php

namespace App\Console\Commands;

use App\Exports\DataExport;
use App\Exports\DataCalculationValuesExport;
use App\Models\Country;
use App\Models\IndexConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;

class ExportMSRawData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:ms-raw-data {--y|year= : The raw data year} {--c|country= : The raw data country id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create export raw data excel file for given year and country';

    public function validateOptions($options)
    {
        return validator(
            $options,
            [
                'year' => 'required|integer',
                'country' => 'required|integer'
            ],
            [
                'year.required' => 'The year option is required.',
                'year.integer' => 'The year option must be integer.',
                'country.required' => 'The country option is required.',
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
        
        $index_configuration = IndexConfiguration::getExistingPublishedConfigurationForYear($year);

        if (is_null($index_configuration))
        {
            $this->error("Index configuration record was not found for year: '{$year}'!");

            return Command::FAILURE;
        }

        $country = Country::find($country_option);
            
        if (is_null($country))
        {
            $this->error("Country record was not found for id: '{$country_option}'!");

            return Command::FAILURE;
        }

        $results = storage_path() . '/app/index_calculations/' . $year . '/' . $index_configuration->id . '/results/EUCSI-results.xlsx';
        
        if (!File::exists($results))
        {
            $this->error("Results xls was not found for year: '{$year}'!");

            return Command::FAILURE;
        }

        try
        {
            $path = storage_path() . '/app';
            $export_data_filename = 'EUCSI-MS-export-data-' . $year . '-' . $country->iso . '.xlsx';
            $data_calculation_values_filename = 'EUCSI-MS-data-calculation-values-' . $year . '-' . $country->iso . '.xlsx';
            $raw_data_filename = 'EUCSI-MS-raw-data-' . $year . '-' . $country->iso . '.xlsx';

             // Get Table of Contents
             $table_of_contents = 'Table-of-Contents';

             $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
             $reader->setReadDataOnly(true);
             $table_of_contents_spreadsheet = $reader->load(database_path() . '/seeders/Importers/import-files/' . $year . '/' . $table_of_contents . '.xlsx');
        
            // Get Export Data
            Excel::store(new DataExport([$country_option], ['survey', 'eurostat', 'shodan'], $year, 1), $export_data_filename);

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $export_data_spreadsheet = $reader->load($path . '/' . $export_data_filename);

            $export_data_sheets = $export_data_spreadsheet->getAllSheets();

            // Copy Raw Data
            File::copy($results, $path . '/' . $raw_data_filename);

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $raw_data_spreadsheet = $reader->load($path . '/' . $raw_data_filename);

            $raw_data_sheets = $raw_data_spreadsheet->getAllSheets();

            foreach ($raw_data_sheets as $raw_data_sheet)
            {
                $title = $raw_data_sheet->getTitle();

                if (!preg_match('/Meta|Effective/', $title) || str_contains($title, "Analysis.Treated")) {

                    $raw_data_spreadsheet->removeSheetByIndex(
                        $raw_data_spreadsheet->getIndex(
                            $raw_data_spreadsheet->getSheetByName($title)
                        )
                    );
                }
            }

            // Add Overview, Survey - Raw Values, Eurostat - Raw Values
            $index = 0;
            foreach ($export_data_sheets as $export_data_sheet)
            {
                $title = $export_data_sheet->getTitle();

                if ($title != 'Indicator Values') {
                    $raw_data_spreadsheet->addExternalSheet($export_data_sheet, $index++);
                }
            }

            // After you're done processing the spreadsheet, unload it to free memory
            $export_data_spreadsheet->disconnectWorksheets();
            unset($export_data_spreadsheet);

            File::delete($path . '/' . $export_data_filename);

            // Get Data Calculation Values
            Excel::store(new DataCalculationValuesExport($index_configuration, $results, $country), $data_calculation_values_filename);

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $data_calculation_values_spreadsheet = $reader->load($path . '/' . $data_calculation_values_filename);

            // Add Data Calculation Values
            $raw_data_spreadsheet->addExternalSheet($data_calculation_values_spreadsheet->getSheet(0), $index++);

            // After you're done processing the spreadsheet, unload it to free memory
            $data_calculation_values_spreadsheet->disconnectWorksheets();
            unset($data_calculation_values_spreadsheet);

            File::delete($path . '/' . $data_calculation_values_filename);

            $raw_data_spreadsheet->addExternalSheet($table_of_contents_spreadsheet->getSheet(0), 0);

            // Add hyperlinks
            $table_of_contents_sheet = str_replace('-', ' ', $table_of_contents);

            $raw_data_spreadsheet->getSheetByName($table_of_contents_sheet)->getCell('B2')->getHyperlink()->setUrl("sheet://'Overview'!A1");
            $raw_data_spreadsheet->getSheetByName($table_of_contents_sheet)->getCell('B4')->getHyperlink()->setUrl("sheet://'Survey - Raw Values'!A1");
            $raw_data_spreadsheet->getSheetByName($table_of_contents_sheet)->getCell('B6')->getHyperlink()->setUrl("sheet://'Eurostat - Raw Values'!A1");
            $raw_data_spreadsheet->getSheetByName($table_of_contents_sheet)->getCell('B8')->getHyperlink()->setUrl("sheet://'Shodan - Indicator Values'!A1");
            $raw_data_spreadsheet->getSheetByName($table_of_contents_sheet)->getCell('B10')->getHyperlink()->setUrl("sheet://'Data Calculation Values'!A1");
            $raw_data_spreadsheet->getSheetByName($table_of_contents_sheet)->getCell('B19')->getHyperlink()->setUrl("sheet://'Meta.Ind'!A1");
            $raw_data_spreadsheet->getSheetByName($table_of_contents_sheet)->getCell('B36')->getHyperlink()->setUrl("sheet://'Meta.Lineage'!A1");
            $raw_data_spreadsheet->getSheetByName($table_of_contents_sheet)->getCell('B38')->getHyperlink()->setUrl("sheet://'Effective.Weights'!A1");

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($raw_data_spreadsheet);
            $writer->save($path . '/' . $raw_data_filename);
        }
        catch (\Exception $e)
        {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->info('MS raw data exported successfully!');

        return Command::SUCCESS;
    }
}
