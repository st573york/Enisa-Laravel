<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class SurveyIndicatorOption extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];

    public function survey_indicator()
    {
        return $this->belongsTo(SurveyIndicator::class);
    }

    public function option()
    {
        return $this->belongsTo(IndicatorAccordionQuestionOption::class);
    }

    public static function getSurveyIndicatorOption($survey_indicator, $option)
    {
        return self::where('survey_indicator_id', $survey_indicator->id)
            ->where('option_id', $option->id)
            ->first();
    }

    public static function getSurveyIndicatorOptions($survey_indicator, $question)
    {
        return self::where('survey_indicator_id', $survey_indicator->id)
            ->whereHas('option', function ($query) use ($question) {
                $query->where('question_id', $question->id);
            })
            ->get()
            ->pluck('option.value')
            ->toArray();
    }

    public static function updateOrCreateSurveyIndicatorOption($data)
    {
        self::updateOrCreate(
            [
                'survey_indicator_id' => $data['survey_indicator_id'],
                'option_id' => $data['option_id']
            ],
            $data
        );
    }

    public static function deleteSurveyIndicatorOption($survey_indicator, $option)
    {
        self::where('survey_indicator_id', $survey_indicator->id)
            ->where('option_id', $option->id)->delete();
    }
}
