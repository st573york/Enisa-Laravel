<?php

namespace App\Models;

use App\HelperFunctions\QuestionnaireCountryHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable;

class IndicatorRequestedChange extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        // Item has been deleted?
        if (!empty($data['old_values']))
        {
            $questionnaire_country = QuestionnaireCountry::find($this->getAttribute('questionnaire_country_id'));
            $data['new_values']['country'] = $questionnaire_country->country->name;

            $indicator = Indicator::find($this->getAttribute('indicator_id'));
            $data['new_values']['indicator'] = $indicator->name;

            $data['new_values']['changes'] = $this->getAttribute('deadline');
            $data['new_values']['deadline'] = $this->getAttribute('deadline');

            $requested_by = User::find($this->getAttribute('requested_by'));
            $data['new_values']['requested_by'] = $requested_by->name;

            $requested_to = User::find($this->getAttribute('requested_to'));
            $data['new_values']['requested_to'] = $requested_to->name;

            unset($data['old_values']);
        }

        return $data;
    }

    public function questionnaire_country()
    {
        return $this->belongsTo(QuestionnaireCountry::class);
    }

    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }

    public function user_requested_by()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function user_requested_to()
    {
        return $this->belongsTo(User::class, 'requested_to');
    }

    public static function getQuestionnaireCountryRequestedChanges($questionnaire, $states = [], $include_role = true)
    {
        return IndicatorRequestedChange::select(
            'indicator_requested_changes.*',
            'indicators.order AS order',
            'users1.name AS requested_by_name',
            'users2.name AS requested_to_name',
            'roles.id AS requested_by_role'
        )
            ->leftJoin('indicators', 'indicators.id', '=', 'indicator_requested_changes.indicator_id')
            ->leftJoin('permissions', 'permissions.user_id', '=', 'indicator_requested_changes.requested_by')
            ->leftJoin('roles', 'roles.id', '=', 'permissions.role_id')
            ->leftJoin('users AS users1', 'users1.id', '=', 'indicator_requested_changes.requested_by')
            ->leftJoin('users AS users2', 'users2.id', '=', 'indicator_requested_changes.requested_to')
            ->where('questionnaire_country_id', $questionnaire->id)
            ->when($include_role && Auth::user()->isAdmin(), function ($query) {
                $query->where('roles.id', 1);
            })
            ->when($include_role && Auth::user()->isPoC(), function ($query) {
                if (Auth::user()->isPrimaryPoC()) {
                    $query->where('roles.id', 5);
                }
                else {
                    $query->where('roles.id', 2);
                }
            })
            ->when(!empty($states), function ($query) use ($states) {
                $query->whereIn('state', $states);
            })
            ->get();
    }

    public static function getLatestQuestionnaireCountryRequestedChanges($questionnaire, $states = [], $order_by = 'requested_at')
    {
        return IndicatorRequestedChange::leftJoin('permissions', 'permissions.user_id', '=', 'indicator_requested_changes.requested_by')
            ->leftJoin('roles', 'roles.id', '=', 'permissions.role_id')
            ->where('questionnaire_country_id', $questionnaire->id)
            ->when(Auth::user()->isAdmin(), function ($query) {
                $query->where('roles.id', 1);
            })
            ->when(Auth::user()->isPoC(), function ($query) {
                if (Auth::user()->isPrimaryPoC()) {
                    $query->where('roles.id', 5);
                }
                else {
                    $query->where('roles.id', 2);
                }
            })
            ->when(!empty($states), function ($query) use ($states) {
                $query->whereIn('state', $states);
            })
            ->orderBy($order_by, 'desc')
            ->first();
    }
    
    public static function getPendingIndicatorRequestedChanges($questionnaire, $indicator)
    {
        return IndicatorRequestedChange::where('questionnaire_country_id', $questionnaire->id)
            ->where('indicator_id', $indicator->id)
            ->where('state', 1)
            ->first();
    }

    public static function getLatestIndicatorRequestedChanges($questionnaire, $indicator, $states = [])
    {
        return IndicatorRequestedChange::where('questionnaire_country_id', $questionnaire->id)
            ->where('indicator_id', $indicator->id)
            ->when(!empty($states), function ($query) use ($states) {
                $query->whereIn('state', $states);
            })
            ->orderBy('requested_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    public static function getIndicatorRequestedChanges($questionnaire, $indicator, $states = [])
    {
        return IndicatorRequestedChange::where('questionnaire_country_id', $questionnaire->id)
            ->where('indicator_id', $indicator->id)
            ->when(!empty($states), function ($query) use ($states) {
                $query->whereIn('state', $states);
            })
            ->get();
    }

    public static function updateOrCreateRequestedChanges($data)
    {
        $requested_changes_id = (isset($data['id'])) ? $data['id'] : null;

        IndicatorRequestedChange::disableAuditing();
        IndicatorRequestedChange::updateOrCreate(
            ['id' => $requested_changes_id],
            $data
        );
        IndicatorRequestedChange::enableAuditing();
    }

    public static function answerQuestionnaireCountryRequestedChanges($questionnaire)
    {
        IndicatorRequestedChange::where('questionnaire_country_id', $questionnaire->id)
            ->where('requested_to', Auth::user()->id)
            ->whereNull('answered_at')
            ->update(['answered_at' => Carbon::now()]);
    }

    public static function discardRequestedChanges($pending_requested_changes)
    {
        IndicatorRequestedChange::find($pending_requested_changes->id)->delete();
    }

    public static function getQuestionnaireCountryRequestedChangesNotifyData($questionnaire, $pending_requested_changes)
    {
        $notify_data = [];

        foreach ($pending_requested_changes as $pending_requested_change)
        {
            $survey_indicator = SurveyIndicator::getSurveyIndicator($questionnaire, $pending_requested_change->indicator);
            $indicator = $survey_indicator->indicator;

            if (!isset($notify_data[$pending_requested_change->requested_to])) {
                $notify_data[$pending_requested_change->requested_to] = [];
            }
            array_push($notify_data[$pending_requested_change->requested_to], $indicator->order . '. ' . $indicator->name);
        }

        return $notify_data;
    }
}
