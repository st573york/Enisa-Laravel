<?php

namespace App\Models;

use App\HelperFunctions\QuestionnaireCountryHelper;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Indicator extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $casts = [
        'configuration_json' => 'array',
    ];
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        switch ($data['event'])
        {
            case 'created':
                $data['description'] = 'The user created a new indicator';

                break;
            case 'updated':
                $data['description'] = 'The user updated an existing indicator';

                break;
            case 'deleted':
                $data['description'] = 'The user deleted an indicator';

                break;
            default:
                break;
        }
        
        $indicator = Indicator::find($data['auditable_id']);
        // Created / Updated
        if ($indicator)
        {
            $data['auditable_name'] = $indicator->name;
            $data['new_values']['year'] = $indicator->year;
        }
        // Deleted
        else
        {
            if (Arr::has($data, 'old_values.name')) {
                $data['auditable_name'] = $data['old_values']['name'];
            }
            if (Arr::has($data, 'old_values.year')) {
                $data['new_values']['year'] = $data['old_values']['year'];
            }
        }

        if (Arr::has($data, 'new_values.default_subarea_id')) {
            $data['new_values']['subarea'] = (!is_null($data['new_values']['default_subarea_id'])) ? Subarea::find($data['new_values']['default_subarea_id'])->name : '';
        }

        if (Arr::has($data, 'new_values.category')) {
            $data['new_values']['indicator_type'] = config('constants.DEFAULT_CATEGORIES')[$data['new_values']['category']];
        }

        unset($data['new_values']['id'],
              $data['new_values']['order'],
              $data['new_values']['validated'],
              $data['new_values']['short_name'],
              $data['new_values']['default_subarea_id'],
              $data['new_values']['default_input_weight'],
              $data['new_values']['identifier'],
              $data['new_values']['category']);

        return $data;
    }

    public function default_subarea()
    {
        return $this->belongsTo(Subarea::class);
    }

    public function questionnaires()
    {
        return $this->hasMany(QuestionnaireIndicator::class);
    }

    public function subarea()
    {
        return $this->belongsTo(Area::class, 'default_subarea_id');
    }

    public function variables()
    {
        return $this->hasMany(IndicatorCalculationVariable::class);
    }

    public function disclaimers()
    {
        return $this->hasOne(IndicatorDisclaimer::class);
    }

    public function accordions()
    {
        return $this->hasMany(IndicatorAccordion::class);
    }

    public static function getIndicator($id)
    {
        return self::find($id);
    }

    public static function getLastIndicator($year, $identifier = null)
    {
        return self::where('year', '<', $year)
            ->when(!is_null($identifier), function ($query) use ($identifier) {
                $query->where('identifier', $identifier);
            })
            ->orderBy('year', 'desc')->first();
    }

    public static function getIndicators($year, $category = null)
    {
        return self::with('default_subarea')
            ->when(!is_null($category), function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->where('year', $year)
            ->orderBy('category')
            ->orderBy('order')
            ->orderBy('identifier')
            ->get();
    }

    public static function getIndicatorsWithIds($ids)
    {
        return self::whereIn('id', $ids)->orderBy('order')->get();
    }

    public static function getIndicatorsWithSurvey($year)
    {
        $indicators = [];

        $indicatorsWithSurvey = self::where('category', 'survey')->where('year', $year)->orderBy('order')->get();
        
        foreach ($indicatorsWithSurvey as $indicatorWithSurvey)
        {
            if($indicatorWithSurvey->accordions()->count()) {
                $indicators[$indicatorWithSurvey->id] = $indicatorWithSurvey;
            }
        }

        return $indicators;
    }

    public static function getIndicatorsWithSurveyAndAnswers($questionnaire_country)
    {
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire_country->id);
        
        $indicators = [];

        if (isset($data['indicators_assigned']['id']))
        {
            foreach ($data['indicators_assigned']['id'] as $indicator_id)
            {
                $indicator = Indicator::find($indicator_id);

                if($indicator->accordions()->count()) {
                    $indicators[$indicator->id] = $indicator;
                }
            }
        }

        return $indicators;
    }
}
