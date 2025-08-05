<?php

namespace App\HelperFunctions;

use App\Models\IndexConfiguration;

class IndexYearChoiceHelper
{
    public static function getIndexYearChoices()
    {
        $yearChoices = [];
        $officialIndexConfigurations = IndexConfiguration::getPublishedConfigurations();

        foreach ($officialIndexConfigurations as $configuration) {
            $yearChoices[$configuration->id] = $configuration->year;
        }

        arsort($yearChoices);

        return $yearChoices;
    }

    public static function getIndexConfigurationYearChoices()
    {
        $yearChoices = config('constants.YEARS_TO_DATE_AND_NEXT');

        arsort($yearChoices);

        return $yearChoices;
    }
}
