<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class TreatedPointsExport implements WithMultipleSheets
{
    use Exportable;

    protected $results;
    protected $countryCode;
    
    public function __construct($results, $countryCode)
    {
        $this->results = $results;
        $this->countryCode = $countryCode;
    }

    public function sheets(): array
    {
        $sheets = [];
        
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($this->results);

        $sheet = $spreadsheet->getSheetByName('Analysis.Treated.Treated_Points');

        $rows = $sheet->toArray();

        $codes = [];
        $country = [];

        foreach ($rows as $row)
        {
            if ($row[0] == 'uCode') {
                $codes = $row;
            }

            if ($row[0] == $this->countryCode) {
                $country = $row;
            }
        }

        $country = array_combine($codes, $country);
        ksort($country, SORT_NATURAL);

        array_push($sheets, new MSTreatedPointsExport($country));

        return $sheets;
    }
}
