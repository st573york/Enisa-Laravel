<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class ReportDataExport implements WithMultipleSheets
{
    use Exportable;

    protected $country;
    protected $data;

    public function __construct($country, $data)
    {
        $this->country = $country;
        $this->data = $data;
    }

    public function sheets(): array
    {
        $sheets = [];

        if (is_null($this->country))
        {
            array_push($sheets, new EUOverviewReportExport($this->data));
            array_push($sheets, new EUWideIndicatorsReportExport($this->data));
            array_push($sheets, new EUDomainsOfExcellenceReportExport($this->data));
            array_push($sheets, new EUDomainsOfImprovementReportExport($this->data));
        }
        else
        {
            array_push($sheets, new MSOverviewReportExport($this->data));
            array_push($sheets, new MSDomainsOfExcellenceReportExport($this->data));
            array_push($sheets, new MSDomainsOfExcellenceDiffReportExport($this->data));
            array_push($sheets, new MSDomainsOfImprovementReportExport($this->data));
            array_push($sheets, new MSDomainsOfImprovementDiffReportExport($this->data));
        }
        
        return $sheets;
    }
}
