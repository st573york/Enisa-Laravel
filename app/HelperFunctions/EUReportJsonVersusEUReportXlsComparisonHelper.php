<?php

namespace App\HelperFunctions;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class EUReportJsonVersusEUReportXlsComparisonHelper
{
    private static $active_sheet;

    private static function validateOverviewSheetHeader(&$rows, &$xls_errors)
    {
        $header = array_shift($rows);
        if (!is_null($header[0])) {
            $xls_errors[self::$active_sheet]['Header']['A1'] = FilesComparisonHelper::formatResult('blank', $header[0]);
        }
        if (FilesComparisonHelper::valuesDiff($header[1], 'Area')) {
            $xls_errors[self::$active_sheet]['Header']['B1'] = FilesComparisonHelper::formatResult('Area', $header[1]);
        }
        if (FilesComparisonHelper::valuesDiff($header[2], 'Subarea')) {
            $xls_errors[self::$active_sheet]['Header']['C1'] = FilesComparisonHelper::formatResult('Subarea', $header[2]);
        }
        if (FilesComparisonHelper::valuesDiff($header[3], 'EU Avg')) {
            $xls_errors[self::$active_sheet]['Header']['D1'] = FilesComparisonHelper::formatResult('EU Avg', $header[3]);
        }
        if (FilesComparisonHelper::valuesDiff($header[4], 'Countries Above')) {
            $xls_errors[self::$active_sheet]['Header']['E1'] = FilesComparisonHelper::formatResult('Countries Above', $header[4]);
        }
        if (FilesComparisonHelper::valuesDiff($header[5], 'Countries Below')) {
            $xls_errors[self::$active_sheet]['Header']['F1'] = FilesComparisonHelper::formatResult('Countries Below', $header[5]);
        }
        if (FilesComparisonHelper::valuesDiff($header[6], 'Countries Around')) {
            $xls_errors[self::$active_sheet]['Header']['G1'] = FilesComparisonHelper::formatResult('Countries Around', $header[6]);
        }
        if (FilesComparisonHelper::valuesDiff($header[7], 'Deviation Avg')) {
            $xls_errors[self::$active_sheet]['Header']['H1'] = FilesComparisonHelper::formatResult('Deviation Avg', $header[7]);
        }
        if (FilesComparisonHelper::valuesDiff($header[8], 'Deviation Max')) {
            $xls_errors[self::$active_sheet]['Header']['I1'] = FilesComparisonHelper::formatResult('Deviation Max', $header[8]);
        }
        if (FilesComparisonHelper::valuesDiff($header[9], 'Deviation Min')) {
            $xls_errors[self::$active_sheet]['Header']['J1'] = FilesComparisonHelper::formatResult('Deviation Min', $header[9]);
        }
        if (FilesComparisonHelper::valuesDiff($header[10], 'Speedometer')) {
            $xls_errors[self::$active_sheet]['Header']['K1'] = FilesComparisonHelper::formatResult('Speedometer', $header[10]);
        }
        if (FilesComparisonHelper::valuesDiff($header[11], 'Reference Year')) {
            $xls_errors[self::$active_sheet]['Header']['L1'] = FilesComparisonHelper::formatResult('Reference Year', $header[11]);
        }
        if (FilesComparisonHelper::valuesDiff($header[12], 'Source')) {
            $xls_errors[self::$active_sheet]['Header']['M1'] = FilesComparisonHelper::formatResult('Source', $header[12]);
        }
    }

    private static function validateOverviewSheetIndex(&$rows, &$excel_index, &$xls_errors)
    {
        $excel_index = null;
        foreach ($rows as $key => $row)
        {
            // Skip and remove empty rows
            if (is_null($row[0]))
            {
                unset($rows[$key]);

                continue;
            }

            if (FilesComparisonHelper::valuesEqual($row[0], 'Index'))
            {
                $excel_index = $row;
                $excel_index['type'] = 'index';
                $excel_index['row'] = $key + 2;

                unset($rows[$key]);

                break;
            }
        }
        
        if (is_null($excel_index))
        {
            $xls_errors[self::$active_sheet]['Index'] = FilesComparisonHelper::formatResult('Index');

            return false;
        }
        
        $excel_row = $excel_index['row'];

        if (FilesComparisonHelper::valuesDiff($excel_index[1], '-')) {
            $xls_errors[self::$active_sheet]['B' . $excel_row] = FilesComparisonHelper::formatResult('-', $excel_index[1]);
        }
        if (FilesComparisonHelper::valuesDiff($excel_index[2], '-')) {
            $xls_errors[self::$active_sheet]['C' . $excel_row] = FilesComparisonHelper::formatResult('-', $excel_index[2]);
        }

        return true;
    }

    private static function validateOverviewSheetArea($db_area, &$rows, &$excel_area, &$xls_errors)
    {
        $excel_area = null;
        foreach ($rows as $key => $row)
        {
            // Skip and remove empty rows
            if (is_null($row[0]))
            {
                unset($rows[$key]);

                continue;
            }
                
            if (FilesComparisonHelper::valuesEqual($row[0], $db_area->name))
            {
                $excel_area = $row;
                $excel_area[0] = FilesComparisonHelper::normalizeString($excel_area[0]);
                $excel_area['type'] = 'area';
                $excel_area['row'] = $key + 2;

                unset($rows[$key]);

                break;
            }
        }

        if (is_null($excel_area))
        {
            if (!isset($xls_errors[self::$active_sheet]['Areas'])) {
                $xls_errors[self::$active_sheet]['Areas'] = [];
            }

            array_push($xls_errors[self::$active_sheet]['Areas'], FilesComparisonHelper::formatResult($db_area->name));

            return false;
        }

        $excel_row = $excel_area['row'];

        if (FilesComparisonHelper::valuesDiff($excel_area[1], '-')) {
            $xls_errors[self::$active_sheet]['B' . $excel_row] = FilesComparisonHelper::formatResult('-', $excel_area[1]);
        }
        if (FilesComparisonHelper::valuesDiff($excel_area[2], '-')) {
            $xls_errors[self::$active_sheet]['C' . $excel_row] = FilesComparisonHelper::formatResult('-', $excel_area[2]);
        }

        return true;
    }

    private static function validateOverviewSheetSubarea($db_subarea, &$rows, &$excel_subarea, &$xls_errors)
    {
        $excel_subarea = null;
        foreach ($rows as $key => $row)
        {
            // Skip and remove empty rows
            if (is_null($row[0]))
            {
                unset($rows[$key]);

                continue;
            }
            
            if (FilesComparisonHelper::valuesEqual($row[0], $db_subarea->name))
            {
                $excel_subarea = $row;
                $excel_subarea[0] = FilesComparisonHelper::normalizeString($excel_subarea[0]);
                $excel_subarea['type'] = 'subarea';
                $excel_subarea['row'] = $key + 2;

                unset($rows[$key]);

                break;
            }
        }
        
        if (is_null($excel_subarea))
        {
            if (!isset($xls_errors[self::$active_sheet]['Subareas'])) {
                $xls_errors[self::$active_sheet]['Subareas'] = [];
            }

            array_push($xls_errors[self::$active_sheet]['Subareas'], FilesComparisonHelper::formatResult($db_subarea->name));

            return false;
        }

        $excel_row = $excel_subarea['row'];

        if (FilesComparisonHelper::valuesDiff($excel_subarea[1], $db_subarea->default_area->name)) {
            $xls_errors[self::$active_sheet]['B' . $excel_row] = FilesComparisonHelper::formatResult($db_subarea->default_area->name, $excel_subarea[1]);
        }
        if (FilesComparisonHelper::valuesDiff($excel_subarea[2], '-')) {
            $xls_errors[self::$active_sheet]['C' . $excel_row] = FilesComparisonHelper::formatResult('-', $excel_subarea[2]);
        }

        return true;
    }

    private static function validateOverviewSheetIndicator($db_indicator, &$rows, &$excel_indicator, &$xls_errors)
    {
        $excel_indicator = null;
        foreach ($rows as $key => $row)
        {
            // Skip and remove empty rows
            if (is_null($row[0]))
            {
                unset($rows[$key]);

                continue;
            }
            
            if (FilesComparisonHelper::valuesEqual($row[0], $db_indicator->name))
            {
                $excel_indicator = $row;
                $excel_indicator[0] = FilesComparisonHelper::normalizeString($excel_indicator[0]);
                $excel_indicator['type'] = 'indicator';
                $excel_indicator['row'] = $key + 2;

                unset($rows[$key]);

                break;
            }
        }
        
        if (is_null($excel_indicator))
        {
            if (!isset($xls_errors[self::$active_sheet]['Indicators'])) {
                $xls_errors[self::$active_sheet]['Indicators'] = [];
            }

            array_push($xls_errors[self::$active_sheet]['Indicators'], FilesComparisonHelper::formatResult($db_indicator->name));

            return false;
        }

        $excel_row = $excel_indicator['row'];

        if (FilesComparisonHelper::valuesDiff($excel_indicator[1], $db_indicator->default_subarea->default_area->name)) {
            $xls_errors[self::$active_sheet]['B' . $excel_row] = FilesComparisonHelper::formatResult($db_indicator->default_subarea->default_area->name, $excel_indicator[1]);
        }
        if (FilesComparisonHelper::valuesDiff($excel_indicator[2], $db_indicator->default_subarea->name)) {
            $xls_errors[self::$active_sheet]['C' . $excel_row] = FilesComparisonHelper::formatResult($db_indicator->default_subarea->name, $excel_indicator[2]);
        }

        return true;
    }

    private static function compareOverviewSheetWithJsonValues($json_data, $excel_item, &$xls_errors)
    {
        $type = $excel_item['type'];
        if ($type == 'index') {
            $json_item = $json_data['index'];
        }
        elseif ($type == 'area') {
            $json_item = $json_data['areas'][$excel_item[0]];
        }
        elseif ($type == 'subarea') {
            $json_item = $json_data['subareas'][$excel_item[0]];
        }
        elseif ($type == 'indicator') {
            $json_item = $json_data['indicators'][$excel_item[0]];
        }
        
        $excel_row = $excel_item['row'];
        
        $val = 3;
        foreach (FilesComparisonHelper::$eu_report_xls_cols as $col)
        {
            if (isset($json_item['scores'][$col]) &&
                FilesComparisonHelper::valuesDiff($json_item['scores'][$col], $excel_item[$val]))
            {
                $excel_col = Coordinate::stringFromColumnIndex($val + 1);
                
                $xls_errors[self::$active_sheet]['Values'][$excel_col . $excel_row] = FilesComparisonHelper::formatResult($json_item['scores'][$col], $excel_item[$val]);
            }

            $val++;
        }
    }

    public static function validateAndCompareOverviewSheet($active_sheet, $properties, $json_data, $sheet, &$xls_errors)
    {
        self::$active_sheet = $active_sheet;

        array_push($xls_errors, [
            self::$active_sheet => [
                'Header' => [],
                'Values' => []
            ]
        ]);

        $rows = $sheet->toArray();
        
        self::validateOverviewSheetHeader($rows, $xls_errors);

        if (self::validateOverviewSheetIndex($rows, $excel_index, $xls_errors)) {
            self::compareOverviewSheetWithJsonValues($json_data, $excel_index, $xls_errors);
        }

        foreach ($properties['areas'] as $db_area)
        {
            if (self::validateOverviewSheetArea($db_area, $rows, $excel_area, $xls_errors)) {
                self::compareOverviewSheetWithJsonValues($json_data, $excel_area, $xls_errors);
            }
        }

        foreach ($properties['subareas'] as $db_subarea)
        {
            if (self::validateOverviewSheetSubarea($db_subarea, $rows, $excel_subarea, $xls_errors)) {
                self::compareOverviewSheetWithJsonValues($json_data, $excel_subarea, $xls_errors);
            }
        }

        foreach ($properties['indicators'] as $db_indicator)
        {
            if (self::validateOverviewSheetIndicator($db_indicator, $rows, $excel_indicator, $xls_errors)) {
                self::compareOverviewSheetWithJsonValues($json_data, $excel_indicator, $xls_errors);
            }
        }
    }

    private static function validateEUWideIndicatorsSheetHeader(&$rows, &$xls_errors)
    {
        $header = array_shift($rows);
        if (FilesComparisonHelper::valuesDiff($header[0], 'Name')) {
            $xls_errors[self::$active_sheet]['Header']['A1'] = FilesComparisonHelper::formatResult('Name', $header[0]);
        }
        if (FilesComparisonHelper::valuesDiff($header[1], 'Algorithm')) {
            $xls_errors[self::$active_sheet]['Header']['B1'] = FilesComparisonHelper::formatResult('Algorithm', $header[1]);
        }
        if (FilesComparisonHelper::valuesDiff($header[2], 'Score')) {
            $xls_errors[self::$active_sheet]['Header']['C1'] = FilesComparisonHelper::formatResult('Score', $header[2]);
        }
        if (FilesComparisonHelper::valuesDiff($header[3], 'Reference Year')) {
            $xls_errors[self::$active_sheet]['Header']['D1'] = FilesComparisonHelper::formatResult('Reference Year', $header[3]);
        }
    }

    private static function validateEUWideIndicatorsSheetIndicator($json_indicator, &$rows, &$excel_indicator, &$xls_errors)
    {
        $excel_indicator = null;
        foreach ($rows as $key => $row)
        {
            // Skip and remove empty rows
            if (is_null($row[0]))
            {
                unset($rows[$key]);

                continue;
            }
            
            if (FilesComparisonHelper::valuesEqual($row[0], $json_indicator['indicator']))
            {
                $excel_indicator = $row;
                $excel_indicator[2] = FilesComparisonHelper::normalizeString($excel_indicator[2]);
                $excel_indicator['type'] = 'indicator';
                $excel_indicator['row'] = $key + 2;

                unset($rows[$key]);

                break;
            }
        }
        
        if (is_null($excel_indicator))
        {
            if (!isset($xls_errors[self::$active_sheet]['Indicators'])) {
                $xls_errors[self::$active_sheet]['Indicators'] = [];
            }

            array_push($xls_errors[self::$active_sheet]['Indicators'], FilesComparisonHelper::formatResult($json_indicator['indicator']));

            return false;
        }

        $excel_row = $excel_indicator['row'];
        
        if (FilesComparisonHelper::valuesDiff($excel_indicator[1], $json_indicator['algorithm'])) {
            $xls_errors[self::$active_sheet]['B' . $excel_row] = FilesComparisonHelper::formatResult($json_indicator['algorithm'], $excel_indicator[1]);
        }

        return true;
    }

    private static function compareEUWideIndicatorsSheetWithJsonValues($json_indicator, $excel_item, &$xls_errors)
    {
        $excel_row = $excel_item['row'];
        
        if (isset($json_indicator['score']) &&
            FilesComparisonHelper::valuesDiff($json_indicator['score'], $excel_item[2]))
        {
            $excel_col = Coordinate::stringFromColumnIndex(3);

            $xls_errors[self::$active_sheet]['Values'][$excel_col . $excel_row] = FilesComparisonHelper::formatResult($json_indicator['score'], $excel_item[2]);
        }
    }

    public static function validateAndCompareEUWideIndicatorsSheet($active_sheet, $json_data, $sheet, &$xls_errors)
    {
        self::$active_sheet = $active_sheet;
        
        array_push($xls_errors, [
            self::$active_sheet => [
                'Header' => [],
                'Values' => []
            ]
        ]);

        $rows = $sheet->toArray();

        self::validateEUWideIndicatorsSheetHeader($rows, $xls_errors);

        foreach ($json_data['eu_wide_indicators'] as $json_indicator)
        {
            if (self::validateEUWideIndicatorsSheetIndicator($json_indicator, $rows, $excel_indicator, $xls_errors)) {
                self::compareEUWideIndicatorsSheetWithJsonValues($json_indicator, $excel_indicator, $xls_errors);
            }
        }
    }

    private static function validatePerformingIndicatorsSheetHeader(&$rows, &$xls_errors)
    {
        $header = array_shift($rows);
        if (FilesComparisonHelper::valuesDiff($header[0], 'Area')) {
            $xls_errors[self::$active_sheet]['Header']['A1'] = FilesComparisonHelper::formatResult('Area', $header[0]);
        }
        if (FilesComparisonHelper::valuesDiff($header[1], 'Subarea')) {
            $xls_errors[self::$active_sheet]['Header']['B1'] = FilesComparisonHelper::formatResult('Subarea', $header[1]);
        }
        if (FilesComparisonHelper::valuesDiff($header[2], 'Indicator')) {
            $xls_errors[self::$active_sheet]['Header']['C1'] = FilesComparisonHelper::formatResult('Indicator', $header[2]);
        }
        if (FilesComparisonHelper::valuesDiff($header[3], 'Algorithm')) {
            $xls_errors[self::$active_sheet]['Header']['D1'] = FilesComparisonHelper::formatResult('Algorithm', $header[3]);
        }
        if (FilesComparisonHelper::valuesDiff($header[4], 'EU Avg')) {
            $xls_errors[self::$active_sheet]['Header']['E1'] = FilesComparisonHelper::formatResult('EU Avg', $header[4]);
        }
        if (FilesComparisonHelper::valuesDiff($header[5], 'Countries Above')) {
            $xls_errors[self::$active_sheet]['Header']['F1'] = FilesComparisonHelper::formatResult('Countries Above', $header[5]);
        }
        if (FilesComparisonHelper::valuesDiff($header[6], 'Countries Below')) {
            $xls_errors[self::$active_sheet]['Header']['G1'] = FilesComparisonHelper::formatResult('Countries Below', $header[6]);
        }
        if (FilesComparisonHelper::valuesDiff($header[7], 'Countries Around')) {
            $xls_errors[self::$active_sheet]['Header']['H1'] = FilesComparisonHelper::formatResult('Countries Around', $header[7]);
        }
        if (FilesComparisonHelper::valuesDiff($header[8], 'Deviation Avg')) {
            $xls_errors[self::$active_sheet]['Header']['I1'] = FilesComparisonHelper::formatResult('Deviation Avg', $header[8]);
        }
        if (FilesComparisonHelper::valuesDiff($header[9], 'Deviation Max')) {
            $xls_errors[self::$active_sheet]['Header']['J1'] = FilesComparisonHelper::formatResult('Deviation Max', $header[9]);
        }
        if (FilesComparisonHelper::valuesDiff($header[10], 'Deviation Min')) {
            $xls_errors[self::$active_sheet]['Header']['K1'] = FilesComparisonHelper::formatResult('Deviation Min', $header[10]);
        }
        if (FilesComparisonHelper::valuesDiff($header[11], 'Speedometer')) {
            $xls_errors[self::$active_sheet]['Header']['L1'] = FilesComparisonHelper::formatResult('Speedometer', $header[11]);
        }
        if (FilesComparisonHelper::valuesDiff($header[12], 'Source')) {
            $xls_errors[self::$active_sheet]['Header']['M1'] = FilesComparisonHelper::formatResult('Source', $header[12]);
        }
    }

    private static function validatePerformingIndicatorsSheetIndicator($json_indicator, &$rows, &$excel_indicator, &$xls_errors)
    {
        $excel_indicator = null;
        foreach ($rows as $key => $row)
        {
            // Skip and remove empty rows
            if (is_null($row[0]))
            {
                unset($rows[$key]);

                continue;
            }
            
            if (FilesComparisonHelper::valuesEqual($row[2], $json_indicator['indicator']))
            {
                $excel_indicator = $row;
                $excel_indicator[2] = FilesComparisonHelper::normalizeString($excel_indicator[2]);
                $excel_indicator['type'] = 'indicator';
                $excel_indicator['row'] = $key + 2;

                unset($rows[$key]);

                break;
            }
        }
        
        if (is_null($excel_indicator))
        {
            if (!isset($xls_errors[self::$active_sheet]['Indicators'])) {
                $xls_errors[self::$active_sheet]['Indicators'] = [];
            }

            array_push($xls_errors[self::$active_sheet]['Indicators'], FilesComparisonHelper::formatResult($json_indicator['indicator']));

            return false;
        }

        $excel_row = $excel_indicator['row'];
        
        if (FilesComparisonHelper::valuesDiff($excel_indicator[0], $json_indicator['area'])) {
            $xls_errors[self::$active_sheet]['A' . $excel_row] = FilesComparisonHelper::formatResult($json_indicator['area'], $excel_indicator[0]);
        }
        if (FilesComparisonHelper::valuesDiff($excel_indicator[1], $json_indicator['subarea'])) {
            $xls_errors[self::$active_sheet]['B' . $excel_row] = FilesComparisonHelper::formatResult($json_indicator['subarea'], $excel_indicator[1]);
        }
        if (FilesComparisonHelper::valuesDiff($excel_indicator[3], $json_indicator['algorithm'])) {
            $xls_errors[self::$active_sheet]['D' . $excel_row] = FilesComparisonHelper::formatResult($json_indicator['algorithm'], $excel_indicator[3]);
        }

        return true;
    }

    private static function comparePerformingIndicatorsSheetWithJsonValues($json_indicator, $excel_item, &$xls_errors)
    {
        $excel_row = $excel_item['row'];
        
        $val = 4;
        foreach (FilesComparisonHelper::$eu_report_xls_cols as $col)
        {
            if (isset($json_indicator['scores'][$col]) &&
                FilesComparisonHelper::valuesDiff($json_indicator['scores'][$col], $excel_item[$val]))
            {
                $excel_col = Coordinate::stringFromColumnIndex($val + 1);

                $xls_errors[self::$active_sheet]['Values'][$excel_col . $excel_row] = FilesComparisonHelper::formatResult($json_indicator['scores'][$col], $excel_item[$val]);
            }

            $val++;
        }
    }

    public static function validateAndComparePerformingIndicatorsSheet($json_data, $sheet, &$xls_errors, $indicators_type)
    {
        self::$active_sheet = ucwords(str_replace('_', ' ', $indicators_type));
        
        array_push($xls_errors, [
            self::$active_sheet => [
                'Header' => [],
                'Values' => []
            ]
        ]);

        $rows = $sheet->toArray();

        self::validatePerformingIndicatorsSheetHeader($rows, $xls_errors);

        foreach ($json_data[$indicators_type] as $json_indicator)
        {
            if (self::validatePerformingIndicatorsSheetIndicator($json_indicator, $rows, $excel_indicator, $xls_errors)) {
                self::comparePerformingIndicatorsSheetWithJsonValues($json_indicator, $excel_indicator, $xls_errors);
            }
        }
    }
}