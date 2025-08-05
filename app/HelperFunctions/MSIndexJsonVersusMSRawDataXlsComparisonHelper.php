<?php

namespace App\HelperFunctions;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class MSIndexJsonVersusMSRawDataXlsComparisonHelper
{
    private static $active_sheet;

    private static function validateOverviewSheetHeader($country, &$rows, &$xls_errors)
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
        if (FilesComparisonHelper::valuesDiff($header[3], 'Weight')) {
            $xls_errors[self::$active_sheet]['Header']['D1'] = FilesComparisonHelper::formatResult('Weight', $header[3]);
        }
        if (FilesComparisonHelper::valuesDiff($header[4], 'EU')) {
            $xls_errors[self::$active_sheet]['Header']['E1'] = FilesComparisonHelper::formatResult('EU', $header[4]);
        }
        if (FilesComparisonHelper::valuesDiff($header[5], $country->iso)) {
            $xls_errors[self::$active_sheet]['Header']['F1'] = FilesComparisonHelper::formatResult($country->iso, $header[5]);
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
        foreach (FilesComparisonHelper::$ms_raw_data_xls_cols as $col)
        {
            if (isset($json_item['values'][$col]) &&
                FilesComparisonHelper::valuesDiff($json_item['values'][$col], $excel_item[$val]))
            {
                $excel_col = Coordinate::stringFromColumnIndex($val + 1);
                
                $xls_errors[self::$active_sheet]['Values'][$excel_col . $excel_row] = FilesComparisonHelper::formatResult($json_item['values'][$col], $excel_item[$val]);
            }

            $val++;
        }
    }

    public static function validateAndCompareOverviewSheet($active_sheet, $properties, $country, $json_data, $sheet, &$xls_errors)
    {
        self::$active_sheet = $active_sheet;

        array_push($xls_errors, [
            self::$active_sheet => [
                'Header' => [],
                'Values' => []
            ]
        ]);

        $rows = $sheet->toArray();
        
        self::validateOverviewSheetHeader($country, $rows, $xls_errors);

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
}