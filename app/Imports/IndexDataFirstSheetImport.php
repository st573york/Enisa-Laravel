<?php

namespace App\Imports;

use App\HelperFunctions\IndexDataImportHelper;
use Maatwebsite\Excel\Concerns\ToArray;

class IndexDataFirstSheetImport implements ToArray
{
    protected $configuration;

    public function __construct($configuration)
    {
        $this->configuration = $configuration;
    }

    public function array(array $array)
    {
        $propertiesTitles = $array[0];
        $countryData = [];
        
        foreach ($array as $key => $row)
        {
            // Skip first row
            if ($key == 0) {
                continue;
            }

            $countryCode = $row[0];
            $countryData = [];

            // Skip first 2 columns to start from 'Index'
            for ($i = 2; $i < count($row); $i++) {
                $countryData[$propertiesTitles[$i]] = $row[$i];
            }
            
            IndexDataImportHelper::formatDataForSave($countryCode, $this->configuration, $countryData);
        }

        return $array;
    }
}
