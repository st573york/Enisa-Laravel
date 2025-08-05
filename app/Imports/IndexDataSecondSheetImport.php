<?php

namespace App\Imports;

use App\HelperFunctions\IndexDataImportHelper;
use Maatwebsite\Excel\Concerns\ToArray;

class IndexDataSecondSheetImport implements ToArray
{
    protected $configuration;

    public function __construct($configuration)
    {
        $this->configuration = $configuration;
    }

    public function array(array $array)
    {
        $countryData = [];
        foreach ($array as $key => $row)
        {
            if ($key == 0 ||
                $row[0] == 'Index')
            {
                continue;
            }

            $countryData[$row[0]] = $row[3];
        }

        foreach ($this->configuration->index as $index)
        {
            $indexJson = IndexDataImportHelper::addNormalizedWeightsToJson($index->json_data, $countryData);
            
            $index->json_data = $indexJson;
            $index->save();
        }

        $baseline = $this->configuration->baseline;
        $indexJson = IndexDataImportHelper::addNormalizedWeightsToJson($baseline->json_data, $countryData);

        $baseline->json_data = $indexJson;
        $baseline->save();

        return $array;
    }
}
