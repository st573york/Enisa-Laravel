<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class SurveyIndicatorAnswer extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];

    public function survey_indicator()
    {
        return $this->belongsTo(SurveyIndicator::class);
    }

    public function question()
    {
        return $this->belongsTo(IndicatorAccordionQuestion::class);
    }

    public function choice()
    {
        return $this->belongsTo(IndicatorQuestionChoice::class);
    }

    public static function getSurveyIndicatorAnswer($survey_indicator, $question, $choice = null, $free_text = null, $reference_year = null, $reference_source = null)
    {
        return self::where('survey_indicator_id', $survey_indicator->id)
            ->where('question_id', $question->id)
            ->when(!is_null($choice), function ($query) use ($choice) {
                $query->where('choice_id', $choice->id);
            })
            ->when(!is_null($free_text), function ($query) use ($free_text) {
                $query->where('free_text', $free_text);
            })
            ->when(!is_null($reference_year), function ($query) use ($reference_year) {
                $query->where('reference_year', $reference_year);
            })
            ->when(!is_null($reference_source), function ($query) use ($reference_source) {
                $query->where('reference_source', $reference_source);
            })
            ->first();
    }

    public static function updateOrCreateSurveyIndicatorAnswer($data)
    {
        self::updateOrCreate(
            [
                'survey_indicator_id' => $data['survey_indicator_id'],
                'question_id' => $data['question_id']
            ],
            $data
        );
    }
}
