<?php

namespace App\Exports;

use App\HelperFunctions\DataExportHelper;
use App\Models\Indicator;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SurveyExcelExport implements WithMultipleSheets
{
    use Exportable;

    protected $year;
    protected $questionnaire_country;

    public function __construct($year, $questionnaire_country)
    {
        $this->year = $year;
        $this->questionnaire_country = $questionnaire_country;
    }

    public function sheets(): array
    {
        $sheets = [];

        $indicators = Indicator::getIndicators($this->year, 'survey');

        array_push($sheets, new SurveyInfoTemplateExport($indicators));
        
        foreach ($indicators as $indicator)
        {
            if ($indicator->accordions()->count())
            {
                $indicatorData = DataExportHelper::getIndicatorData($indicator);
                
                array_push($sheets, new SurveyIndicatorTemplateExport($this->questionnaire_country, $indicatorData));
            }
        }

        return $sheets;
    }
}
