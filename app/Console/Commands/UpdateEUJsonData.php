<?php

namespace App\Console\Commands;

use App\Models\BaselineIndex;
use App\Models\IndexConfiguration;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

class UpdateEUJsonData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eu:update-json {--field= : The field in the table} {--y|year= : The index configuration year} {--file= : The json data that will overwrite the existing one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the EU json/report data for given field, year and json file';

    public function validateOptions($options)
    {
        return validator(
            $options,
            [
                'field' => ['required', Rule::in(['data', 'report'])],
                'year' => 'required|integer',
                'file' => 'required'
            ],
            [
                'field.required' => 'The field option is required.',
                'field.in' => 'The field option is invalid. Valid options \'data\', \'report\'.',
                'year.required' => 'The year option is required.',
                'year.integer' => 'The year option must be integer.',
                'file.required' => 'The file option is required.'
            ]
        );
    }

    public function validateFile($file)
    {
        if (!File::exists($file))
        {
            $this->error('File does not exist!');

            return true;
        }

        if (File::extension($file) != 'json')
        {
            $this->error('File must have a .json extension!');

            return true;
        }

        return false;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ret = false;

        $field_option = $this->option('field');
        $year = $this->option('year');
        $file = $this->option('file');
        
        $validator = $this->validateOptions($this->options());
        if ($ret |= $validator->fails())
        {
            foreach ($validator->messages()->toArray() as $message) {
                $this->error($message[0]);
            }
        }
        
        $ret |= $this->validateFile($file);
        
        if ($ret) {
            return Command::FAILURE;
        }
        
        $index_configuration = IndexConfiguration::getExistingPublishedConfigurationForYear($year);

        if (is_null($index_configuration))
        {
            $this->error("Index configuration record was not found for year: '{$year}'!");

            return Command::FAILURE;
        }

        $field = ($field_option == 'data') ? 'json_data' : 'report_json';
        
        $this->info("EU '{$field}' for year: '{$year}' will be updated.");

        if ($this->confirm('Do you wish to continue?'))
        {
            $contents = File::get($file);

            $data = json_decode($contents, true);
        
            BaselineIndex::where('index_configuration_id', $index_configuration->id)->update([$field => $data]);

            $this->info('Json data was successfully updated!');

            return Command::SUCCESS;
        }
    }
}
