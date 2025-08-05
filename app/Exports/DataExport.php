<?php

namespace App\Exports;

use App\HelperFunctions\DataExportHelper;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class DataExport implements WithMultipleSheets
{
    use Exportable;

    protected $countries;
    protected $sources;
    protected $year;
    protected $indexDataFlag;
    protected $data;

    public function __construct($countries, $sources, $year, $indexDataFlag = false)
    {
        $this->countries = $countries;
        $this->sources = $sources;
        $this->year = $year;
        $this->indexDataFlag = $indexDataFlag;
        $this->data = DataExportHelper::prepareIndexOverviewData($this->countries, $this->year);
    }

    public function sheets(): array
    {
        $sheets = [];
        
        if ($this->indexDataFlag &&
            isset($this->data['indiceRows']['index']) &&
            isset($this->data['indiceRows']['areas']) &&
            isset($this->data['indiceRows']['subareas']) &&
            isset($this->data['indiceRows']['indicators']))
        {
            array_push($sheets, new IndexOverviewExport($this->data));
        }

        $data = DataExportHelper::prepareIndicatorValuesData($this->countries, $this->year, $this->sources, $this->data);

        if (!empty($data['countryCodes']) &&
            !empty($data['indicatorValuesData']))
        {
            array_push($sheets, new IndicatorValueComparisonExport($this->sources, $data));
        }

        if (in_array('manual', $this->sources) &&
            count($this->countries) > 1)
        {
            $data = DataExportHelper::prepareEUWideIndicatorValuesData($this->countries, $this->year, $this->sources);
            
            if (!empty($data['indicatorValuesData'])) {
                array_push($sheets, new EUWideIndicatorValueComparisonExport($data));
            }
        }

        if (in_array('survey', $this->sources))
        {
            $data = DataExportHelper::prepareSurveyIndicatorRawData($this->countries, $this->year);

            if (!empty($data['countryCodes']) &&
                !empty($data['indicatorValuesData']))
            {
                array_push($sheets, new SurveyIndicatorRawDataComparisonExport($data));
            }
        }

        if (in_array('eurostat', $this->sources))
        {
            $data = DataExportHelper::prepareEurostatIndicatorRawData($this->countries, $this->year);

            if (!empty($data['countryCodes']) &&
                !empty($data['indicatorValuesData']))
            {
                array_push($sheets, new EurostatIndicatorRawDataComparisonExport($data));
            }
        }

        if (in_array('shodan', $this->sources))
        {
            $data = DataExportHelper::prepareShodanIndicatorRawData($this->countries, $this->year, ['manual'], ['shodan']);
            
            if (!empty($data['countryCodes']) &&
                !empty($data['indicatorValuesData']))
            {
                array_push($sheets, new ShodanIndicatorValueComparisonExport($data));
            }
        }

        return $sheets;
    }
}
