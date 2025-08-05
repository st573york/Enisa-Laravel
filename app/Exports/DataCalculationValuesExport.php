<?php

namespace App\Exports;

use App\Models\Area;
use App\Models\Indicator;
use App\Models\Subarea;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class DataCalculationValuesExport implements WithMultipleSheets
{
    use Exportable;

    protected $index;
    protected $results;
    protected $country;
    
    public function __construct($index, $results, $country)
    {
        $this->index = $index;
        $this->results = $results;
        $this->country = $country;
    }

    public function sheets(): array
    {
        $arr = [];
        
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($this->results);

        $sheets = $spreadsheet->getAllSheets();

        $data = [];

        foreach ($sheets as $sheet)
        {
            $codes = [];
            $country_data = [];
            $eu = [];

            $title = $sheet->getTitle();

            if (preg_match('/data|treated_points|fullscore/', strtolower($title)))
            {
                $parts = explode('.', $title);
                $type = strtolower(end($parts));

                $rows = $sheet->toArray();

                foreach ($rows as $row)
                {
                    if ($row[0] == 'uCode') {
                        $codes = $row;
                    }

                    if ($row[0] == $this->country->code) {
                        $country_data = $row;
                    }
                    elseif ($row[0] == $this->country->iso) {
                        $country_data = $row;
                    }

                    if ($row[0] == config('constants.EU_AVERAGE_CODE')) {
                        $eu = $row;
                    }
                }
                
                $country_data = array_combine($codes, $country_data);
                ksort($country_data, SORT_NATURAL);
                
                if (!empty($eu))
                {
                    $eu = array_combine($codes, $eu);
                    ksort($eu, SORT_NATURAL);
                }
                
                foreach ($codes as $code)
                {
                    $strtolower_code = strtolower($code);

                    if (!preg_match('/^ucode|ind_|subarea_|area_|index/', $strtolower_code)) {
                        continue;
                    }

                    if ($code == 'uCode')
                    {
                        $data[$code][$type]['country'] = $country_data[$code];
                        if (!empty($eu)) {
                            $data[$code][$type]['eu'] = $eu[$code];
                        }
                    }
                    else
                    {
                        if (preg_match('/^ind_/', $strtolower_code))
                        {
                            $parts = explode('_', $code);
                            $data['data'][$code]['name'] = Indicator::where('identifier', $parts[1])->where('year', $this->index->year)->value('name');
                        }
                        elseif (preg_match('/^subarea_/', $strtolower_code))
                        {
                            $parts = explode('_', $code);
                            $data['data'][$code]['name'] = Subarea::where('identifier', $parts[1])->where('year', $this->index->year)->value('name');
                        }
                        elseif (preg_match('/^area_/', $strtolower_code))
                        {
                            $parts = explode('_', $code);
                            $data['data'][$code]['name'] = Area::where('identifier', $parts[1])->where('year', $this->index->year)->value('name');
                        }
                        elseif (preg_match('/^index/', $strtolower_code)) {
                            $data['data'][$code]['name'] = $this->index->name;
                        }

                        $data['data'][$code][$type]['country'] = $country_data[$code];
                        if (!empty($eu)) {
                            $data['data'][$code][$type]['eu'] = $eu[$code];
                        }
                    }
                }
            }
        }
        
        array_push($arr, new MSDataCalculationValuesExport($data));

        return $arr;
    }
}
