<?php

namespace App\HelperFunctions;

use Carbon\Carbon;

class GeneralHelper
{
    public static function dateFormat($date, $format = 'Y-m-d')
    {
        return Carbon::parse($date)->format($format);
    }

    public static function convertText($text)
    {
        return preg_replace('/\s+/', ' ', htmlspecialchars_decode($text));
    }

    public static function convertSpecialCharacters($data)
    {
        if (!is_null($data)) {
            return htmlspecialchars($data);
        }

        return $data;
    }

    public static function showDisclaimers($disclaimers)
    {
        $disclaimersSuperscript = "";
        $nonZeroDisclaimers = array_filter($disclaimers, function($a) { return $a !== 0; });

        if (!empty($nonZeroDisclaimers))
        {
           $list = implode(', ', $nonZeroDisclaimers);
           $disclaimersSuperscript = "<sup>(" . $list .")</sup>";
        }

        return $disclaimersSuperscript;
    }

    public static function calculateRange($difference)
    {
        if ($difference < -10) {
            return 'lesser range-Low';
        }
        elseif ($difference <= 10 && $difference > -10) {
            return 'range-Med';
        }
        elseif ($difference > 10) {
            return 'range-High';
        }
    }

    public static function calculateRangeEU($numberOfCountries)
    {
        // Find and return the key with the maximum value
        return array_search(max($numberOfCountries),  $numberOfCountries);
    }

    public static function truncateScores($algorithmText)
    {
        if(str_contains($algorithmText, "Source: ")) {
            $pos = strpos($algorithmText, "Source: ");
            $algorithmText = substr($algorithmText, 0, $pos);
        }

        if(str_contains($algorithmText, "Value = ")) {
            $pos = strpos($algorithmText, "Value = ");
            $algorithmText = substr($algorithmText, 0, $pos);
        }

        return $algorithmText;
    }
}