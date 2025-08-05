<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class SurveyIndicator extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];

    public function questionnaire_country()
    {
        return $this->belongsTo(QuestionnaireCountry::class);
    }

    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class);
    }

    public function approved_by()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(SurveyIndicatorAnswer::class);
    }

    public function options()
    {
        return $this->hasMany(SurveyIndicatorOption::class);
    }

    public static function getSurveyIndicator($questionnaire, $indicator)
    {
        return self::where('questionnaire_country_id', $questionnaire->id)
            ->where('indicator_id', $indicator->id)
            ->first();
    }

    public static function getSurveyIndicators($questionnaire, $indicators = [])
    {
        return self::where('questionnaire_country_id', $questionnaire->id)
            ->when(!empty($indicators), function ($query) use ($indicators) {
                $query->whereIn('indicator_id', $indicators);
            })
            ->get();
    }

    public static function updateOrCreateSurveyIndicator($data)
    {
        return self::updateOrCreate(
            [
                'questionnaire_country_id' => $data['questionnaire_country_id'],
                'indicator_id' => $data['indicator_id']
            ],
            $data
        );
    }

    public static function saveSurveyIndicator($survey_indicator)
    {
        self::disableAuditing();
        $survey_indicator->save();
        self::enableAuditing();
    }
}
