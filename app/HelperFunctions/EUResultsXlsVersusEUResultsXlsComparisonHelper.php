<?php

namespace App\HelperFunctions;

class EUResultsXlsVersusEUResultsXlsComparisonHelper
{
    public static function validateEUResultsData($active_sheet, $results_data_first, $results_data_second, &$xls_errors)
    {
        foreach ($results_data_first as $country => $country_data)
        {
            if (!array_key_exists($country, $results_data_second))
            {
                array_push($xls_errors[$active_sheet], FilesComparisonHelper::formatResult($country));

                continue;
            }

            foreach ($country_data as $item => $item_data)
            {
                if (!isset($xls_errors[$active_sheet][$country])) {
                    $xls_errors[$active_sheet][$country] = [];
                }
                
                if (!isset($results_data_second[$country][$item])) {
                    array_push($xls_errors[$active_sheet][$country], FilesComparisonHelper::formatResult($item));
                }
                elseif (FilesComparisonHelper::valuesDiff($item_data, $results_data_second[$country][$item])) {
                    $xls_errors[$active_sheet][$country][$item] = FilesComparisonHelper::formatResult($item_data, $results_data_second[$country][$item]);
                }
            }
        }
    }

    public static function getEUResultsData($sheet)
    {
        $rows = $sheet->toArray();
        $header = array_shift($rows);

        $results = [];
        foreach ($rows as $row)
        {
            $row_data = array_combine($header, $row);
            ksort($row_data);

            $results[$row[0]] = $row_data;
        }

        return $results;
    }
}