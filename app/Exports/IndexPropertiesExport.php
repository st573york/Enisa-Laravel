<?php

namespace App\Exports;

use App\HelperFunctions\DataExportHelper;
use App\Models\Area;
use App\Models\Indicator;
use App\Models\Subarea;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class IndexPropertiesExport implements WithMultipleSheets
{
    use Exportable;

    protected $year;

    public function __construct($year)
    {
        $this->year = $year;
    }

    public function sheets(): array
    {
        $sheets = [];

        $areas = Area::getAreas($this->year);
        $subareas = Subarea::getSubareas($this->year);
        $indicators = Indicator::getIndicators($this->year);

        array_push($sheets, new IndexExcelExport($areas, 'areas', true));
        array_push($sheets, new IndexExcelExport($subareas, 'subareas', true));
        array_push($sheets, new IndexExcelExport($indicators, 'indicators', true));

        foreach ($indicators as $indicator)
        {
            if ($indicator->accordions()->count())
            {
                $indicatorData = DataExportHelper::getIndicatorData($indicator);

                array_push($sheets, new SurveyIndicatorConfigurationExport($indicatorData));
            }
        }

        return $sheets;
    }
}
