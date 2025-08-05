<?php

namespace App\Console\Commands;

use App\HelperFunctions\EUReportJsonVersusEUReportXlsComparisonHelper;
use App\HelperFunctions\EUResultsXlsVersusEUResultsXlsComparisonHelper;
use App\HelperFunctions\FilesComparisonHelper;
use App\HelperFunctions\MSIndexJsonVersusMSRawDataXlsComparisonHelper;
use App\HelperFunctions\MSReportJsonVersusMSReportXlsComparisonHelper;
use App\Models\Area;
use App\Models\Country;
use App\Models\Indicator;
use App\Models\Subarea;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

class CompareFiles extends Command
{
    const COMPARISON = ['MS Report Json VS MS Report Xls', 'EU Report Json VS EU Report Xls', 'MS Index Json VS MS Raw Data Xls', 'EU-results Xls VS EU-results Xls'];

    private static $year;
    private static $directory;
    private static $countries;
    private static $files = [];
    private static $missing_files = [];
    private static $errors = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:compare-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compare values between files';

    public function __construct()
    {
        parent::__construct();

        self::$directory = storage_path('app/files_to_compare');
        self::$countries = Country::whereNotNull('iso')->get();
    }

    public function validateYear()
    {
        if (empty(self::$year))
        {
            $this->error('Year cannot be empty.');

            return false;
        }

        if (filter_var(self::$year, FILTER_VALIDATE_INT) === false)
        {
            $this->error('Year must be an integer.');

            return false;
        }

        return true;
    }

    public function validateFilename(&$filename)
    {
        if (empty($filename))
        {
            $this->error('Filename cannot be empty.');

            return false;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $filename .= (empty($extension)) ? '.xlsx' : '';

        if (!File::exists(self::$directory . '/' . $filename))
        {
            $this->error('File ' . self::$directory . '/' . $filename . ' is missing.');

            return false;
        }

        return true;
    }

    private function checkDirectory()
    {
        if (!count(File::files(self::$directory)))
        {
            $this->error('Directory ' . self::$directory . ' is empty!');

            return false;
        }

        return true;
    }

    private function checkAndUpdateFiles()
    {
        self::$files['EU'] = [
            'report_xls' => 'EUCSI-EU27-report.xlsx',
            'report_json' => 'report-EU.json',
            'index_json' => 'index-EU.json',
            'results' => 'EUCSI-results-' . self::$year . '.xlsx',
            'results-recalculated' => 'EUCSI-results-recalculated.xlsx'
        ];

        foreach (self::$countries as $country)
        {
            self::$files[$country->iso] = [
                'raw_data_xls' => 'EUCSI-MS-raw-data-' . self::$year . '-' . $country->iso . '.xlsx',
                'report_xls' => 'EUCSI-MS-report-' . $country->iso . '.xlsx',
                'report_json' => 'report-' . $country->iso . '.json',
                'index_json' => 'index-' . $country->iso . '.json'
            ];
        }

        foreach (self::$files as $country_files)
        {
            foreach ($country_files as $file)
            {
                if (!File::exists(self::$directory . '/' . $file)) {
                    array_push(self::$missing_files, $file);
                }
            }
        }
    }

    private function getMSReportJsonIndexData($json_data)
    {
        if (isset($json_data[0]['scores'])) {
            return ['scores' => $json_data[0]['scores']];
        }

        return [];
    }

    private function getEUReportJsonIndexData($json_data)
    {
        $index = [];

        if (isset($json_data[0]['scores']['euAverage'])) {
            $index['scores']['euAverage'] = $json_data[0]['scores']['euAverage'];
        }
        if (isset($json_data[0]['scores']['numberOfCountries']['above'])) {
            $index['scores']['numberOfCountries_above'] = $json_data[0]['scores']['numberOfCountries']['above'];
        }
        if (isset($json_data[0]['scores']['numberOfCountries']['below'])) {
            $index['scores']['numberOfCountries_below'] = $json_data[0]['scores']['numberOfCountries']['below'];
        }
        if (isset($json_data[0]['scores']['numberOfCountries']['around'])) {
            $index['scores']['numberOfCountries_around'] = $json_data[0]['scores']['numberOfCountries']['around'];
        }
        if (isset($json_data[0]['deviation']['avg'])) {
            $index['scores']['deviation_avg'] = $json_data[0]['deviation']['avg'];
        }
        if (isset($json_data[0]['deviation']['max'])) {
            $index['scores']['deviation_max'] = $json_data[0]['deviation']['max'];
        }
        if (isset($json_data[0]['deviation']['min'])) {
            $index['scores']['deviation_min'] = $json_data[0]['deviation']['min'];
        }
        if (isset($json_data[0]['speedometer'])) {
            $index['scores']['speedometer'] = $json_data[0]['speedometer'];
        }
        
        return $index;
    }

    private function getIndexJsonIndexValues($json_data, $value_type, $value_field)
    {
        if (isset($json_data['contents'][0]['global_index_values'][0][$value_field])) {
            return ['values' => [$value_type => $json_data['contents'][0]['global_index_values'][0][$value_field]]];
        }

        return [];
    }

    private function getMSReportJsonAreasData($json_data)
    {
        $areas = [];

        foreach ($json_data[0]['areas'] as $area_data)
        {
            $area = FilesComparisonHelper::normalizeString($area_data['name']);

            $areas[$area] = [];
            if (isset($area_data['scores'])) {
                $areas[$area]['scores'] = $area_data['scores'];
            }
        }

        return $areas;
    }

    private function getEUReportJsonAreasData($json_data)
    {
        $areas = [];

        foreach ($json_data[0]['areas'] as $area_data)
        {
            $area = FilesComparisonHelper::normalizeString($area_data['name']);

            $areas[$area] = [];
            if (isset($area_data['scores'][0]['euAverage'])) {
                $areas[$area]['scores']['euAverage'] = $area_data['scores'][0]['euAverage'];
            }
            if (isset($area_data['scores'][0]['numberOfCountries']['above'])) {
                $areas[$area]['scores']['numberOfCountries_above'] = $area_data['scores'][0]['numberOfCountries']['above'];
            }
            if (isset($area_data['scores'][0]['numberOfCountries']['below'])) {
                $areas[$area]['scores']['numberOfCountries_below'] = $area_data['scores'][0]['numberOfCountries']['below'];
            }
            if (isset($area_data['scores'][0]['numberOfCountries']['around'])) {
                $areas[$area]['scores']['numberOfCountries_around'] = $area_data['scores'][0]['numberOfCountries']['around'];
            }
            if (isset($area_data['scores'][0]['deviation'][0]['avg'])) {
                $areas[$area]['scores']['deviation_avg'] = $area_data['scores'][0]['deviation'][0]['avg'];
            }
            if (isset($area_data['scores'][0]['deviation'][0]['max'])) {
                $areas[$area]['scores']['deviation_max'] = $area_data['scores'][0]['deviation'][0]['max'];
            }
            if (isset($area_data['scores'][0]['deviation'][0]['min'])) {
                $areas[$area]['scores']['deviation_min'] = $area_data['scores'][0]['deviation'][0]['min'];
            }
            if (isset($area_data['scores'][0]['speedometer'])) {
                $areas[$area]['scores']['speedometer'] = $area_data['scores'][0]['speedometer'];
            }
        }
        
        return $areas;
    }

    private function getIndexJsonAreasData($json_data, $value_type, $value_field)
    {
        $areas = [];

        foreach ($json_data['contents'] as $key => $content)
        {
            if ($key === 0) {
                continue;
            }

            $area_data = $content['area'];
            $area = FilesComparisonHelper::normalizeString($area_data['name']);

            $areas[$area] = [];
            if (isset($area_data['values'][0][$value_field])) {
                $areas[$area]['values'][$value_type] = $area_data['values'][0][$value_field];
            }
            if (isset($area_data['weight'])) {
                $areas[$area]['weight'][$value_type] = $area_data['weight'];
            }
        }

        return $areas;
    }

    private function getMSReportJsonSubareasData($json_data)
    {
        $subareas = [];

        foreach ($json_data[0]['areas'] as $area)
        {
            foreach ($area['subareas'] as $subarea_data)
            {
                $subarea = FilesComparisonHelper::normalizeString($subarea_data['name']);

                $subareas[$subarea] = [];
                if (isset($subarea_data['scores'])) {
                    $subareas[$subarea]['scores'] = $subarea_data['scores'];
                }
            }
        }

        return $subareas;
    }

    private function getEUReportJsonSubareasData($json_data)
    {
        $subareas = [];

        foreach ($json_data[0]['areas'] as $area)
        {
            foreach ($area['subareas'] as $subarea_data)
            {
                $subarea = FilesComparisonHelper::normalizeString($subarea_data['name']);

                $subareas[$subarea] = [];
                if (isset($subarea_data['scores'][0]['euAverage'])) {
                    $subareas[$subarea]['scores']['euAverage'] = $subarea_data['scores'][0]['euAverage'];
                }
                if (isset($subarea_data['scores'][0]['numberOfCountries']['above'])) {
                    $subareas[$subarea]['scores']['numberOfCountries_above'] = $subarea_data['scores'][0]['numberOfCountries']['above'];
                }
                if (isset($subarea_data['scores'][0]['numberOfCountries']['below'])) {
                    $subareas[$subarea]['scores']['numberOfCountries_below'] = $subarea_data['scores'][0]['numberOfCountries']['below'];
                }
                if (isset($subarea_data['scores'][0]['numberOfCountries']['around'])) {
                    $subareas[$subarea]['scores']['numberOfCountries_around'] = $subarea_data['scores'][0]['numberOfCountries']['around'];
                }
                if (isset($subarea_data['scores'][0]['deviation'][0]['avg'])) {
                    $subareas[$subarea]['scores']['deviation_avg'] = $subarea_data['scores'][0]['deviation'][0]['avg'];
                }
                if (isset($subarea_data['scores'][0]['deviation'][0]['max'])) {
                    $subareas[$subarea]['scores']['deviation_max'] = $subarea_data['scores'][0]['deviation'][0]['max'];
                }
                if (isset($subarea_data['scores'][0]['deviation'][0]['min'])) {
                    $subareas[$subarea]['scores']['deviation_min'] = $subarea_data['scores'][0]['deviation'][0]['min'];
                }
                if (isset($subarea_data['scores'][0]['speedometer'])) {
                    $subareas[$subarea]['scores']['speedometer'] = $subarea_data['scores'][0]['speedometer'];
                }
            }
        }
        
        return $subareas;
    }

    private function getIndexJsonSubareasData($json_data, $value_type, $value_field)
    {
        $subareas = [];

        foreach ($json_data['contents'] as $key => $content)
        {
            if ($key === 0) {
                continue;
            }

            foreach ($content['area']['subareas'] as $subarea_data)
            {
                $subarea = FilesComparisonHelper::normalizeString($subarea_data['name']);

                $subareas[$subarea] = [];
                if (isset($subarea_data['values'][0][$value_field])) {
                    $subareas[$subarea]['values'][$value_type] = $subarea_data['values'][0][$value_field];
                }
                if (isset($subarea_data['weight'])) {
                    $subareas[$subarea]['weight'][$value_type] = $subarea_data['weight'];
                }
            }
        }

        return $subareas;
    }

    private function getMSReportJsonIndicatorsData($json_data)
    {
        $indicators = [];

        foreach ($json_data[0]['allIndicators'][0]['areas'] as $area)
        {
            foreach ($area['subareas'] as $subarea)
            {
                foreach ($subarea['indicators'] as $indicator_data)
                {
                    $indicator = FilesComparisonHelper::normalizeString($indicator_data['indicator']);

                    $indicators[$indicator]['scores'] = [];
                    if (isset($indicator_data['euAverage'])) {
                        $indicators[$indicator]['scores']['euAverage'] = $indicator_data['euAverage'];
                    }
                    if (isset($indicator_data['countryScore'])) {
                        $indicators[$indicator]['scores']['country'] = $indicator_data['countryScore'];
                    }
                    if (isset($indicator_data['difference'])) {
                        $indicators[$indicator]['scores']['difference'] = $indicator_data['difference'];
                    }
                    if (isset($indicator_data['speedometer'])) {
                        $indicators[$indicator]['scores']['speedometer'] = $indicator_data['speedometer'];
                    }
                }
            }
        }

        return $indicators;
    }

    private function getEUReportJsonIndicatorsData($json_data)
    {
        $indicators = [];

        foreach ($json_data[0]['allIndicators'][0]['areas'] as $area)
        {
            foreach ($area['subareas'] as $subarea)
            {
                foreach ($subarea['indicators'] as $indicator_data)
                {
                    $indicator = FilesComparisonHelper::normalizeString($indicator_data['indicator']);

                    $indicators[$indicator] = [];
                    if (isset($indicator_data['scores'][0]['euAverage'])) {
                        $indicators[$indicator]['scores']['euAverage'] = $indicator_data['scores'][0]['euAverage'];
                    }
                    if (isset($indicator_data['scores'][0]['numberOfCountries']['above'])) {
                        $indicators[$indicator]['scores']['numberOfCountries_above'] = $indicator_data['scores'][0]['numberOfCountries']['above'];
                    }
                    if (isset($indicator_data['scores'][0]['numberOfCountries']['below'])) {
                        $indicators[$indicator]['scores']['numberOfCountries_below'] = $indicator_data['scores'][0]['numberOfCountries']['below'];
                    }
                    if (isset($indicator_data['scores'][0]['numberOfCountries']['around'])) {
                        $indicators[$indicator]['scores']['numberOfCountries_around'] = $indicator_data['scores'][0]['numberOfCountries']['around'];
                    }
                    if (isset($indicator_data['scores'][0]['deviation'][0]['avg'])) {
                        $indicators[$indicator]['scores']['deviation_avg'] = $indicator_data['scores'][0]['deviation'][0]['avg'];
                    }
                    if (isset($indicator_data['scores'][0]['deviation'][0]['max'])) {
                        $indicators[$indicator]['scores']['deviation_max'] = $indicator_data['scores'][0]['deviation'][0]['max'];
                    }
                    if (isset($indicator_data['scores'][0]['deviation'][0]['min'])) {
                        $indicators[$indicator]['scores']['deviation_min'] = $indicator_data['scores'][0]['deviation'][0]['min'];
                    }
                    if (isset($indicator_data['scores'][0]['speedometer'])) {
                        $indicators[$indicator]['scores']['speedometer'] = $indicator_data['scores'][0]['speedometer'];
                    }
                }
            }
        }
        
        return $indicators;
    }

    private function getIndexJsonIndicatorsData($json_data, $value_type, $value_field)
    {
        $indicators = [];

        foreach ($json_data['contents'] as $key => $content)
        {
            if ($key === 0) {
                continue;
            }

            foreach ($content['area']['subareas'] as $subarea)
            {
                foreach ($subarea['indicators'] as $indicator_data)
                {
                    $indicator = FilesComparisonHelper::normalizeString($indicator_data['name']);

                    $indicators[$indicator] = [];
                    if (isset($indicator_data['values'][0][$value_field])) {
                        $indicators[$indicator]['values'][$value_type] = $indicator_data['values'][0][$value_field];
                    }
                    if (isset($indicator_data['weight'])) {
                        $indicators[$indicator]['weight'][$value_type] = $indicator_data['weight'];
                    }
                }
            }
        }

        return $indicators;
    }

    private function getMSReportJsonPerformingIndicatorsData($json_data, $indicators_type)
    {
        $indicators = [];

        foreach ($json_data[0][$indicators_type] as $indicator_data)
        {
            $indicator = FilesComparisonHelper::normalizeString($indicator_data['indicator']);
            
            $indicators[$indicator] = [
                'area' => FilesComparisonHelper::normalizeString($indicator_data['areaName']),
                'subarea' => FilesComparisonHelper::normalizeString($indicator_data['subareaName']),
                'indicator' => $indicator,
                'algorithm' => FilesComparisonHelper::normalizeString($indicator_data['algorithm']),
                'scores' => []
            ];

            if (isset($indicator_data['euAverage'])) {
                $indicators[$indicator]['scores']['euAverage'] = $indicator_data['euAverage'];
            }
            if (isset($indicator_data['countryScore'])) {
                $indicators[$indicator]['scores']['country'] = $indicator_data['countryScore'];
            }
            if (isset($indicator_data['difference'])) {
                $indicators[$indicator]['scores']['difference'] = $indicator_data['difference'];
            }
            if (isset($indicator_data['speedometer'])) {
                $indicators[$indicator]['scores']['speedometer'] = $indicator_data['speedometer'];
            }
        }

        return $indicators;
    }

    private function getEUReportJsonEUWideIndicatorsData($json_data)
    {
        $indicators = [];

        foreach ($json_data[0]['euWideIndicators'] as $indicator_data)
        {
            $indicator = FilesComparisonHelper::normalizeString($indicator_data['indicator']);

            $indicators[$indicator] = [
                'indicator' => $indicator,
                'algorithm' => FilesComparisonHelper::normalizeString($indicator_data['algorithm'])
            ];

            if (isset($indicator_data['score'])) {
                $indicators[$indicator]['score'] = $indicator_data['score'];
            }
        }
        
        return $indicators;
    }

    private function getEUReportJsonPerformingIndicatorsData($json_data, $indicators_type)
    {
        $indicators = [];

        foreach ($json_data[0][$indicators_type] as $indicator_data)
        {
            $indicator = FilesComparisonHelper::normalizeString($indicator_data['indicator']);
            
            $indicators[$indicator] = [
                'area' => FilesComparisonHelper::normalizeString($indicator_data['areaName']),
                'subarea' => FilesComparisonHelper::normalizeString($indicator_data['subareaName']),
                'indicator' => $indicator,
                'algorithm' => FilesComparisonHelper::normalizeString($indicator_data['algorithm']),
                'scores' => []
            ];

            if (isset($indicator_data['scores'][0]['euAverage'])) {
                $indicators[$indicator]['scores']['euAverage'] = $indicator_data['scores'][0]['euAverage'];
            }
            if (isset($indicator_data['scores'][0]['numberOfCountries']['above'])) {
                $indicators[$indicator]['scores']['numberOfCountries_above'] = $indicator_data['scores'][0]['numberOfCountries']['above'];
            }
            if (isset($indicator_data['scores'][0]['numberOfCountries']['below'])) {
                $indicators[$indicator]['scores']['numberOfCountries_below'] = $indicator_data['scores'][0]['numberOfCountries']['below'];
            }
            if (isset($indicator_data['scores'][0]['numberOfCountries']['around'])) {
                $indicators[$indicator]['scores']['numberOfCountries_around'] = $indicator_data['scores'][0]['numberOfCountries']['around'];
            }
            if (isset($indicator_data['scores'][0]['deviation'][0]['avg'])) {
                $indicators[$indicator]['scores']['deviation_avg'] = $indicator_data['scores'][0]['deviation'][0]['avg'];
            }
            if (isset($indicator_data['scores'][0]['deviation'][0]['max'])) {
                $indicators[$indicator]['scores']['deviation_max'] = $indicator_data['scores'][0]['deviation'][0]['max'];
            }
            if (isset($indicator_data['scores'][0]['deviation'][0]['min'])) {
                $indicators[$indicator]['scores']['deviation_min'] = $indicator_data['scores'][0]['deviation'][0]['min'];
            }
            if (isset($indicator_data['scores'][0]['speedometer'])) {
                $indicators[$indicator]['scores']['speedometer'] = $indicator_data['scores'][0]['speedometer'];
            }
        }
        
        return $indicators;
    }

    private function getMSReportJsonData($report_json)
    {
        $json_data = json_decode(file_get_contents(self::$directory . '/' .$report_json), true);
        
        return [
            'index' => $this->getMSReportJsonIndexData($json_data),
            'areas' => $this->getMSReportJsonAreasData($json_data),
            'subareas' => $this->getMSReportJsonSubareasData($json_data),
            'indicators' => $this->getMSReportJsonIndicatorsData($json_data),
            'top_performing_indicators' => $this->getMSReportJsonPerformingIndicatorsData($json_data, 'domains_of_excellence'),
            'top_performing_indicators_diff' => $this->getMSReportJsonPerformingIndicatorsData($json_data, 'domains_of_excellence_diff'),
            'least_performing_indicators' => $this->getMSReportJsonPerformingIndicatorsData($json_data, 'domains_of_improvement'),
            'least_performing_indicators_diff' => $this->getMSReportJsonPerformingIndicatorsData($json_data, 'domains_of_improvement_diff')
        ];
    }

    private function getEUReportJsonData($report_json)
    {
        $json_data = json_decode(file_get_contents(self::$directory . '/' .$report_json), true);

        return [
            'index' => $this->getEUReportJsonIndexData($json_data),
            'areas' => $this->getEUReportJsonAreasData($json_data),
            'subareas' => $this->getEUReportJsonSubareasData($json_data),
            'indicators' => $this->getEUReportJsonIndicatorsData($json_data),
            'eu_wide_indicators' => $this->getEUReportJsonEUWideIndicatorsData($json_data),
            'top_performing_indicators' => $this->getEUReportJsonPerformingIndicatorsData($json_data, 'domains_of_excellence'),
            'least_performing_indicators' => $this->getEUReportJsonPerformingIndicatorsData($json_data, 'domains_of_improvement')
        ];
    }

    private function getIndexJsonData($index_json, $value_type, $value_field)
    {
        $json_data = json_decode(file_get_contents(self::$directory . '/' .$index_json), true);
        
        return [
            'index' => $this->getIndexJsonIndexValues($json_data, $value_type, $value_field),
            'areas' => $this->getIndexJsonAreasData($json_data, $value_type, $value_field),
            'subareas' => $this->getIndexJsonSubareasData($json_data, $value_type, $value_field),
            'indicators' => $this->getIndexJsonIndicatorsData($json_data, $value_type, $value_field)
        ];
    }
    
    private function validateMSReportJsonData($json_data, &$json_errors)
    {
        foreach (FilesComparisonHelper::$ms_report_json_fields as $field)
        {
            // Index
            if (!isset($json_data['index']['scores'][$field]))
            {
                if (!isset($json_errors['Index'])) {
                    $json_errors['Index'] = [];
                }

                array_push($json_errors['Index'], FilesComparisonHelper::formatResult($field));
            }
            // Areas
            foreach ($json_data['areas'] as $name => $json_area)
            {
                if (!isset($json_area['scores'][$field]))
                {
                    if (!isset($json_errors['Areas'][$name])) {
                        $json_errors['Areas'][$name] = [];
                    }

                    array_push($json_errors['Areas'][$name], FilesComparisonHelper::formatResult($field));
                }
            }
            // Subareas
            foreach ($json_data['subareas'] as $name => $json_subarea)
            {
                if (!isset($json_subarea['scores'][$field]))
                {
                    if (!isset($json_errors['Subareas'][$name])) {
                        $json_errors['Subareas'][$name] = [];
                    }

                    array_push($json_errors['Subareas'][$name], FilesComparisonHelper::formatResult($field));
                }
            }
            // Indicators
            foreach ($json_data['indicators'] as $name => $json_indicator)
            {
                if (!isset($json_indicator['scores'][$field]))
                {
                    if (!isset($json_errors['Indicators'][$name])) {
                        $json_errors['Indicators'][$name] = [];
                    }

                    array_push($json_errors['Indicators'][$name], FilesComparisonHelper::formatResult($field));
                }
            }
            // Top Performing Indicators
            foreach ($json_data['top_performing_indicators'] as $name => $json_indicator)
            {
                if (!isset($json_indicator['scores'][$field]))
                {
                    if (!isset($json_errors['Top Performing Indicators'][$name])) {
                        $json_errors['Top Performing Indicators'][$name] = [];
                    }

                    array_push($json_errors['Top Performing Indicators'][$name], FilesComparisonHelper::formatResult($field));
                }
            }
            // Top Performing Indicators Diff
            foreach ($json_data['top_performing_indicators_diff'] as $name => $json_indicator)
            {
                if (!isset($json_indicator['scores'][$field]))
                {
                    if (!isset($json_errors['Top Performing Indicators Diff'][$name])) {
                        $json_errors['Top Performing Indicators Diff'][$name] = [];
                    }

                    array_push($json_errors['Top Performing Indicators Diff'][$name], FilesComparisonHelper::formatResult($field));
                }
            }
            // Least Performing Indicators
            foreach ($json_data['least_performing_indicators'] as $name => $json_indicator)
            {
                if (!isset($json_indicator['scores'][$field]))
                {
                    if (!isset($json_errors['Least Performing Indicators'][$name])) {
                        $json_errors['Least Performing Indicators'][$name] = [];
                    }

                    array_push($json_errors['Least Performing Indicators'][$name], FilesComparisonHelper::formatResult($field));
                }
            }
            // Least Performing Indicators Diff
            foreach ($json_data['least_performing_indicators_diff'] as $name => $json_indicator)
            {
                if (!isset($json_indicator['scores'][$field]))
                {
                    if (!isset($json_errors['Least Performing Indicators Diff'][$name])) {
                        $json_errors['Least Performing Indicators Diff'][$name] = [];
                    }

                    array_push($json_errors['Least Performing Indicators Diff'][$name], FilesComparisonHelper::formatResult($field));
                }
            }
        }
    }

    private function validateEUReportJsonData($json_data, &$json_errors)
    {
        foreach (FilesComparisonHelper::$eu_report_json_fields as $field)
        {
            // Index
            if (!isset($json_data['index']['scores'][$field]))
            {
                if (!isset($json_errors['Index'])) {
                    $json_errors['Index'] = [];
                }

                array_push($json_errors['Index'], FilesComparisonHelper::formatResult(
                    FilesComparisonHelper::formatField($field)
                ));
            }
            // Areas
            foreach ($json_data['areas'] as $name => $json_area)
            {
                if (!isset($json_area['scores'][$field]))
                {
                    if (!isset($json_errors['Areas'][$name])) {
                        $json_errors['Areas'][$name] = [];
                    }

                    array_push($json_errors['Areas'][$name], FilesComparisonHelper::formatResult(
                        FilesComparisonHelper::formatField($field)
                    ));
                }
            }
            // Subareas
            foreach ($json_data['subareas'] as $name => $json_subarea)
            {
                if (!isset($json_subarea['scores'][$field]))
                {
                    if (!isset($json_errors['Subareas'][$name])) {
                        $json_errors['Subareas'][$name] = [];
                    }

                    array_push($json_errors['Subareas'][$name], FilesComparisonHelper::formatResult(
                        FilesComparisonHelper::formatField($field)
                    ));
                }
            }
            // Indicators
            foreach ($json_data['indicators'] as $name => $json_indicator)
            {
                if (!isset($json_indicator['scores'][$field]))
                {
                    if (!isset($json_errors['Indicators'][$name])) {
                        $json_errors['Indicators'][$name] = [];
                    }

                    array_push($json_errors['Indicators'][$name], FilesComparisonHelper::formatResult(
                        FilesComparisonHelper::formatField($field)
                    ));
                }
            }
            // Top Performing Indicators
            foreach ($json_data['top_performing_indicators'] as $name => $json_indicator)
            {
                if (!isset($json_indicator['scores'][$field]))
                {
                    if (!isset($json_errors['Top Performing Indicators'][$name])) {
                        $json_errors['Top Performing Indicators'][$name] = [];
                    }

                    array_push($json_errors['Top Performing Indicators'][$name], FilesComparisonHelper::formatResult(
                        FilesComparisonHelper::formatField($field)
                    ));
                }
            }
            // Least Performing Indicators
            foreach ($json_data['least_performing_indicators'] as $name => $json_indicator)
            {
                if (!isset($json_indicator['scores'][$field]))
                {
                    if (!isset($json_errors['Least Performing Indicators'][$name])) {
                        $json_errors['Least Performing Indicators'][$name] = [];
                    }

                    array_push($json_errors['Least Performing Indicators'][$name], FilesComparisonHelper::formatResult(
                        FilesComparisonHelper::formatField($field)
                    ));
                }
            }
        }
    }

    private function validateIndexJsonData($json_data, &$json_errors)
    {
        foreach (FilesComparisonHelper::$index_json_fields as $field)
        {
            // Index
            if (!isset($json_data['index']['values'])) {
                $json_errors['Index'] = FilesComparisonHelper::formatResult('values');
            }
            // Areas
            foreach ($json_data['areas'] as $name => $json_area)
            {
                if (!isset($json_area[$field]))
                {
                    if (!isset($json_errors['Areas'][$name])) {
                        $json_errors['Areas'][$name] = [];
                    }

                    array_push($json_errors['Areas'][$name], FilesComparisonHelper::formatResult($field));
                }
            }
            // Subareas
            foreach ($json_data['subareas'] as $name => $json_subarea)
            {
                if (!isset($json_subarea[$field]))
                {
                    if (!isset($json_errors['Subareas'][$name])) {
                        $json_errors['Subareas'][$name] = [];
                    }

                    array_push($json_errors['Subareas'][$name], FilesComparisonHelper::formatResult($field));
                }
            }
            // Indicators
            foreach ($json_data['indicators'] as $name => $json_indicator)
            {
                if (!isset($json_indicator[$field]))
                {
                    if (!isset($json_errors['Indicators'][$name])) {
                        $json_errors['Indicators'][$name] = [];
                    }

                    array_push($json_errors['Indicators'][$name], FilesComparisonHelper::formatResult($field));
                }
            }
        }
    }

    private function validateIndexJsonWeights($json_data, &$json_errors)
    {
        foreach ($json_data as $type => &$data)
        {
            // Areas / Subareas / Indicators
            if ($type != 'index')
            {
                foreach ($data as $name => $item_data)
                {
                    if (FilesComparisonHelper::valuesDiff($item_data['weight']['EU'], $item_data['weight']['country'])) {
                        $json_errors['Weights'][$name] = [
                            'EU' => $item_data['weight']['EU'],
                            'country' => $item_data['weight']['country']
                        ];
                    }
                }
            }
        }
    }

    private function updateIndexJsonData(&$json_data)
    {
        foreach ($json_data as $type => &$data)
        {
            // Index
            if ($type == 'index') {
                $data['values'] = ['weight' => '-'] + $data['values'];
            }
            // Areas / Subareas / Indicators
            else
            {
                foreach ($data as &$item_data) {
                    $item_data['values'] = ['weight' => (string)$item_data['weight']['country']] + $item_data['values'];
                }
            }
        }
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        File::ensureDirectoryExists(self::$directory);

        if (!$this->checkDirectory()) {
            return Command::FAILURE;
        }

        $comparison = $this->choice('Select a comparison', self::COMPARISON);

        if ($comparison != 'EU-results Xls VS EU-results Xls')
        {
            self::$year = $this->ask('Enter the year');

            if (!$this->validateYear()) {
                return Command::FAILURE;
            }

            $this->checkAndUpdateFiles();

            if (!empty(self::$missing_files))
            {
                print_r(self::$missing_files);

                $this->error('The above files are missing!');

                return Command::FAILURE;
            }
        }
        
        $properties = [
            'areas' => Area::getAreas(self::$year),
            'subareas' => Subarea::getSubareas(self::$year),
            'indicators' => Indicator::where('category', '!=', 'eu-wide')->where('year', self::$year)->get()
        ];
        
        if ($comparison == 'MS Report Json VS MS Report Xls')
        {
            $this->info('Comparing MS Report Json VS MS Report Xls...');

            $bar = $this->output->createProgressBar(self::$countries->count());
            $bar->start();
            
            foreach (self::$countries as $country)
            {
                $json_errors = [
                    'Index' => [],
                    'Areas' => [],
                    'Subareas' => [],
                    'Indicators' => []
                ];
                $xls_errors = [];

                $report_json = self::$files[$country->iso]['report_json'];
                $report_xls = self::$files[$country->iso]['report_xls'];

                $json_data = $this->getMSReportJsonData($report_json);

                $this->validateMSReportJsonData($json_data, $json_errors);

                $reader = new Xlsx();
                $spreadsheet = $reader->load(self::$directory . '/' . $report_xls);

                $sheets = $spreadsheet->getAllSheets();

                foreach ($sheets as $sheet)
                {
                    $title = $sheet->getTitle();
                    
                    if ($title == 'Overview') {
                        MSReportJsonVersusMSReportXlsComparisonHelper::validateAndCompareOverviewSheet($title, $properties, $country, $json_data, $sheet, $xls_errors);
                    }
                    elseif ($title == 'Top Performing Indicators') {
                        MSReportJsonVersusMSReportXlsComparisonHelper::validateAndComparePerformingIndicatorsSheet($country, $json_data, $sheet, $xls_errors, 'top_performing_indicators');
                    }
                    elseif (preg_match('/Top Performing Indicators D/', $title)) {
                        MSReportJsonVersusMSReportXlsComparisonHelper::validateAndComparePerformingIndicatorsSheet($country, $json_data, $sheet, $xls_errors, 'top_performing_indicators_diff');
                    }
                    elseif ($title == 'Least Performing Indicators') {
                        MSReportJsonVersusMSReportXlsComparisonHelper::validateAndComparePerformingIndicatorsSheet($country, $json_data, $sheet, $xls_errors, 'least_performing_indicators');
                    }
                    elseif (preg_match('/Least Performing Indicators D/', $title)) {
                        MSReportJsonVersusMSReportXlsComparisonHelper::validateAndComparePerformingIndicatorsSheet($country, $json_data, $sheet, $xls_errors, 'least_performing_indicators_diff');
                    }
                }

                // After you're done processing the spreadsheet, unload it to free memory
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                $json_errors = FilesComparisonHelper::removeEmptyValues($json_errors);
                $xls_errors = FilesComparisonHelper::removeEmptyValues($xls_errors);
        
                if (!empty($json_errors)) {
                    self::$errors[$report_json] = $json_errors;
                }
        
                if (!empty($xls_errors)) {
                    self::$errors[$report_xls] = $xls_errors;
                }
                
                $bar->advance();
            }
        }
        elseif ($comparison == 'EU Report Json VS EU Report Xls')
        {
            $this->info('Comparing EU Report Json VS EU Report Xls...');

            $bar = $this->output->createProgressBar(1);
            $bar->start();

            $json_errors = [
                'Index' => [],
                'Areas' => [],
                'Subareas' => [],
                'Indicators' => []
            ];
            $xls_errors = [];

            $report_json = self::$files['EU']['report_json'];
            $report_xls = self::$files['EU']['report_xls'];

            $json_data = $this->getEUReportJsonData($report_json);

            $this->validateEUReportJsonData($json_data, $json_errors);

            $reader = new Xlsx();
            $spreadsheet = $reader->load(self::$directory . '/' . $report_xls);

            $sheets = $spreadsheet->getAllSheets();

            foreach ($sheets as $sheet)
            {
                $title = $sheet->getTitle();
                    
                if ($title == 'Overview') {
                    EUReportJsonVersusEUReportXlsComparisonHelper::validateAndCompareOverviewSheet($title, $properties, $json_data, $sheet, $xls_errors);
                }
                elseif ($title == 'EU Wide Indicators') {
                    EUReportJsonVersusEUReportXlsComparisonHelper::validateAndCompareEUWideIndicatorsSheet($title, $json_data, $sheet, $xls_errors);
                }
                elseif ($title == 'Top-performing domains') {
                    EUReportJsonVersusEUReportXlsComparisonHelper::validateAndComparePerformingIndicatorsSheet($json_data, $sheet, $xls_errors, 'top_performing_indicators');
                }
                elseif ($title == 'Least-performing domains') {
                    EUReportJsonVersusEUReportXlsComparisonHelper::validateAndComparePerformingIndicatorsSheet($json_data, $sheet, $xls_errors, 'least_performing_indicators');
                }
            }

            $json_errors = FilesComparisonHelper::removeEmptyValues($json_errors);
            $xls_errors = FilesComparisonHelper::removeEmptyValues($xls_errors);
            
            if (!empty($json_errors)) {
                self::$errors[$report_json] = $json_errors;
            }

            if (!empty($xls_errors)) {
                self::$errors[$report_xls] = $xls_errors;
            }
        }
        elseif ($comparison == 'MS Index Json VS MS Raw Data Xls')
        {
            $this->info('Comparing MS Index Json VS MS Raw Data Xls...');

            $bar = $this->output->createProgressBar(self::$countries->count());
            $bar->start();

            $json_errors = [
                'Index' => [],
                'Areas' => [],
                'Subareas' => [],
                'Indicators' => []
            ];

            $index_json = self::$files['EU']['index_json'];

            $value_field = 'European Average ' . self::$year;
            
            $eu_json_data = $this->getIndexJsonData($index_json, 'EU', $value_field);
            
            $this->validateIndexJsonData($eu_json_data, $json_errors);

            $json_errors = FilesComparisonHelper::removeEmptyValues($json_errors);
            
            if (!empty($json_errors)) {
                self::$errors[$index_json] = $json_errors;
            }
            
            foreach (self::$countries as $country)
            {
                $json_errors = [
                    'Index' => [],
                    'Areas' => [],
                    'Subareas' => [],
                    'Indicators' => []
                ];
                $xls_errors = [];

                $index_json = self::$files[$country->iso]['index_json'];
                $raw_data_xls = self::$files[$country->iso]['raw_data_xls'];

                $value_field = $country->name . ' ' . self::$year;
                
                $ms_json_data = $this->getIndexJsonData($index_json, 'country', $value_field);
                
                $this->validateIndexJsonData($ms_json_data, $json_errors);

                $json_data = array_merge_recursive($eu_json_data, $ms_json_data);

                $this->validateIndexJsonWeights($json_data, $json_errors);

                $this->updateIndexJsonData($json_data);
                
                $reader = new Xlsx();
                $spreadsheet = $reader->load(self::$directory . '/' . $raw_data_xls);

                $sheets = $spreadsheet->getAllSheets();

                foreach ($sheets as $sheet)
                {
                    $title = $sheet->getTitle();
                    
                    if ($title == 'Overview') {
                        MSIndexJsonVersusMSRawDataXlsComparisonHelper::validateAndCompareOverviewSheet($title, $properties, $country, $json_data, $sheet, $xls_errors);
                    }
                }

                // After you're done processing the spreadsheet, unload it to free memory
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                $json_errors = FilesComparisonHelper::removeEmptyValues($json_errors);
                $xls_errors = FilesComparisonHelper::removeEmptyValues($xls_errors);
        
                if (!empty($json_errors)) {
                    self::$errors[$index_json] = $json_errors;
                }
        
                if (!empty($xls_errors)) {
                    self::$errors[$raw_data_xls] = $xls_errors;
                }
                
                $bar->advance();
            }
        }
        elseif ($comparison = 'EU-results Xls VS EU-results Xls')
        {
            $filename_first = $this->ask('Enter the filename for the EU-results Xls');
            
            if (!$this->validateFilename($filename_first)) {
                return Command::FAILURE;
            }

            $filename_second = $this->ask('Enter the filename for another EU-results Xls');

            if (!$this->validateFilename($filename_second)) {
                return Command::FAILURE;
            }

            $this->info('Comparing EU-results Xls VS EU-results Xls...');

            $bar = $this->output->createProgressBar(1);
            $bar->start();

            $xls_errors = [
                'Results.FullScore' => [],
                'Results.FullRank' => []
            ];

            $reader = new Xlsx();

            $spreadsheet = $reader->load(self::$directory . '/' . $filename_first);

            $sheets = $spreadsheet->getAllSheets();
            
            foreach ($sheets as $sheet)
            {
                $title = $sheet->getTitle();
                    
                if ($title == 'Results.FullScore') {
                    $full_score_data_first = EUResultsXlsVersusEUResultsXlsComparisonHelper::getEUResultsData($sheet);
                }
                elseif ($title == 'Results.FullRank') {
                    $full_rank_data_first = EUResultsXlsVersusEUResultsXlsComparisonHelper::getEUResultsData($sheet);
                }
            }
            
            // After you're done processing the spreadsheet, unload it to free memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $spreadsheet = $reader->load(self::$directory . '/' . $filename_second);

            $sheets = $spreadsheet->getAllSheets();
            
            foreach ($sheets as $sheet)
            {
                $title = $sheet->getTitle();
                    
                if ($title == 'Results.FullScore') {
                    $full_score_data_second = EUResultsXlsVersusEUResultsXlsComparisonHelper::getEUResultsData($sheet);
                }
                elseif ($title == 'Results.FullRank') {
                    $full_rank_data_second = EUResultsXlsVersusEUResultsXlsComparisonHelper::getEUResultsData($sheet);
                }
            }
            
            // After you're done processing the spreadsheet, unload it to free memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            EUResultsXlsVersusEUResultsXlsComparisonHelper::validateEUResultsData('Results.FullScore', $full_score_data_first, $full_score_data_second, $xls_errors);
            EUResultsXlsVersusEUResultsXlsComparisonHelper::validateEUResultsData('Results.FullRank', $full_rank_data_first, $full_rank_data_second, $xls_errors);

            $xls_errors = FilesComparisonHelper::removeEmptyValues($xls_errors);
        
            if (!empty($xls_errors)) {
                self::$errors[$filename_second] = $xls_errors;
            }
                
            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        if (!empty(self::$errors))
        {
            print_r(self::$errors);

            $this->error('Comparison completed with errors!');

            return Command::FAILURE;
        }

        $this->info('Comparison completed successfully!');

        return Command::SUCCESS;
    }
}
