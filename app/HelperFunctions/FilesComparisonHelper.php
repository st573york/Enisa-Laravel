<?php

namespace App\HelperFunctions;

class FilesComparisonHelper
{
    public static $ms_report_json_fields = [
        'euAverage',
        'country',
        'difference',
        'speedometer'
    ];
    public static $eu_report_json_fields = [
        'euAverage',
        'numberOfCountries_above',
        'numberOfCountries_below',
        'numberOfCountries_around',
        'deviation_avg',
        'deviation_max',
        'deviation_min',
        'speedometer'
    ];
    public static $index_json_fields = [
        'values',
        'weight'
    ];
    public static $ms_report_xls_cols = [
        'euAverage',
        'country',
        'difference',
        'speedometer'
    ];
    public static $eu_report_xls_cols = [
        'euAverage',
        'numberOfCountries_above',
        'numberOfCountries_below',
        'numberOfCountries_around',
        'deviation_avg',
        'deviation_max',
        'deviation_min',
        'speedometer'
    ];
    public static $ms_raw_data_xls_cols = [
        'weight',
        'EU',
        'country'
    ];

    public static function formatField($field)
    {
        return str_replace('_', ' ', $field);
    }

    public static function formatResult($expected, $actual = null)
    {
        return "Expected: \e[32m" . $expected . "\e[0m -> " . (is_null($actual) ? "\e[31mNot found\e[0m" : "Found: \e[31m" . $actual . "\e[0m" );
    }

    public static function normalizeString($string)
    {
        // Replace different dashes with a standard hyphen
        $string = str_replace(['–', '—'], '-', $string);

        // Remove non-breaking spaces (U+00A0) and other hidden spaces (U+2000 - U+200F)
        $string = preg_replace('/[\x{00A0}\x{2000}-\x{200F}]/u', ' ', $string);

        // Replace multiple spaces with a single space and trim
        return trim(preg_replace('/\s+/', ' ', $string));
    }

    public static function valuesEqual($str1, $str2)
    {
        if (self::normalizeString($str1) === self::normalizeString($str2)) {
            return true;
        }

        return false;
    }

    public static function valuesDiff($str1, $str2)
    {
        if (self::normalizeString($str1) !== self::normalizeString($str2)) {
            return true;
        }

        return false;
    }

    public static function removeEmptyValues($array)
    {
        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $array[$key] = self::removeEmptyValues($value); // Recursively clean nested arrays
    
                if (empty($array[$key])) {
                    unset($array[$key]);
                }
            }
            elseif ($value === '') {
                unset($array[$key]);
            }
        }

        return $array;
    }
}