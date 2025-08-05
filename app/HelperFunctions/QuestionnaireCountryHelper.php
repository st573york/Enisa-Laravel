<?php

namespace App\HelperFunctions;

use App\Notifications\NotifyUser;
use App\Models\Audit;
use App\Models\Country;
use App\Models\User;
use App\Models\Indicator;
use App\Models\IndicatorAccordion;
use App\Models\IndicatorAccordionQuestion;
use App\Models\IndicatorAccordionQuestionOption;
use App\Models\IndicatorQuestionChoice;
use App\Models\IndicatorRequestedChange;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use App\Models\SurveyIndicator;
use App\Models\SurveyIndicatorAnswer;
use App\Models\SurveyIndicatorOption;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class QuestionnaireCountryHelper
{
    const ERROR_NOT_AUTHORIZED = 'Indicator cannot be updated as you are not authorized for this action!';
    const ERROR_NOT_ALLOWED = 'Indicator cannot be updated as the requested action is not allowed!';

    public static function validateIndicatorInputs($questionnaire, $inputs)
    {
        $inputs['deadline'] = strtotime($inputs['deadline']);
            
        return validator(
            $inputs,
            [
                'assignee' => 'required|in:' . implode(', ', $inputs['users']),
                'deadline' => ['required', 'integer', Rule::when(
                    !filter_var($inputs['requested_changes'], FILTER_VALIDATE_BOOLEAN),
                    'between:' . strtotime(date('d-m-Y')) . ',' . strtotime($questionnaire->questionnaire->deadline)
                )]
            ],
            [
                'deadline.integer' => 'The deadline field is required.',
                'deadline.between' => 'The deadline field must be between ' . date('d-m-Y') . ' and ' . GeneralHelper::dateFormat($questionnaire->questionnaire->deadline, 'd-m-Y') . '.'
            ]
        );
    }

    public static function validateRequestChangesInputs($inputs)
    {
        return validator(
            $inputs,
            [
                'changes' => 'required',
                'deadline' => 'required'
            ]
        );
    }

    public static function validateAnswers($answers, $data)
    {
        $messages = [];

        foreach ($answers as $indicator)
        {
            if (empty($data['indicators_assigned_exact']) ||
                !in_array($indicator['id'], $data['indicators_assigned_exact']['id']))
            {
                continue;
            }

            $db_indicator = Indicator::find($indicator['id']);
            $db_accordion = IndicatorAccordion::getIndicatorAccordion($db_indicator, $indicator['accordion']);
            $db_question = IndicatorAccordionQuestion::getIndicatorAccordionQuestion($db_accordion, $indicator['order']);
            
            $answers = (isset($indicator['answers']) && !empty($indicator['answers'])) ? current($indicator['answers']) : null;
            $reference_year = $indicator['reference_year'];
            $reference_source = $indicator['reference_source'];
            $rating = ($indicator['rating'] > 0) ? $indicator['rating'] : null;

            $inputs = [
                $indicator['inputs']['answers'] => $answers,
                $indicator['inputs']['reference_year'] => $reference_year,
                $indicator['inputs']['reference_source'] => $reference_source,
                $indicator['inputs']['rating'] => $rating
            ];

            $attributes = [];
            $rules = [];
            
            foreach (array_keys($inputs) as $key)
            {
                if (preg_match('/answers/', $key)) {
                    $input = 'answers';
                }
                elseif (preg_match('/reference-year/', $key)) {
                    $input = 'reference_year';
                }
                elseif (preg_match('/reference-source/', $key)) {
                    $input = 'reference_source';
                }
                elseif (preg_match('/rating/', $key)) {
                    $input = 'rating';
                }

                $attributes = array_merge($attributes, [
                    $key => str_replace('_', ' ', $input)
                ]);

                $validation = [];
                if ($db_question->answers_required) {
                    $validation['answers']['required'] = true;
                }
                if ($db_question->reference_required)
                {
                    $validation['reference_year']['required'] = true;
                    $validation['reference_source']['required'] = true;
                }
                $validation['rating']['required'] = true;

                $rule = self::getQuestionValidationRule($input, $validation, $indicator);
                
                if (!empty($rule)) {
                    $rules = array_merge($rules, [
                        $key => $rule
                    ]);
                }
            }
            
            $validator = validator($inputs, $rules, [], $attributes);

            if ($validator->fails()) {
                $messages = array_merge($messages, $validator->messages()->toArray());
            }
        }

        return $messages;
    }

    public static function validateQuestionnaireTemplateUpload($request)
    {
        return validator(
            $request->all(),
            [
                'file' => 'required|file|mimes:xlsx|max:2048',
                'extension' => ['nullable', Rule::in(['xlsx'])]
            ],
            [
                'extension' => 'The file must be of type .xlsx'
            ]
        );
    }

    public static function canUpdateQuestionnaireCountryIndicatorData($inputs)
    {
        if ((Auth::user()->isPoC() && $inputs['action'] == 'edit') ||
            (Auth::user()->isPoC()&& $inputs['action'] == 'approve') ||
            (Auth::user()->isAdmin() && $inputs['action'] == 'final_approve') ||
            (Auth::user()->isAdmin() && $inputs['action'] == 'unapprove'))
        {
            return true;
        }

        return false;
    }

    public static function canRequestChangesQuestionnaireCountryIndicator()
    {
        if (Auth::user()->isPoC() ||
            Auth::user()->isAdmin())
        {
            return true;
        }

        return false;
    }

    public static function canDiscardRequestedChangesQuestionnaireCountryIndicator()
    {
        if (Auth::user()->isPoC() ||
            Auth::user()->isAdmin())
        {
            return true;
        }

        return false;
    }

    public static function canSubmitQuestionnaireCountryRequestedChanges()
    {
        if (Auth::user()->isPoC() ||
            Auth::user()->isAdmin())
        {
            return true;
        }

        return false;
    }

    public static function canLoadResetSurveyIndicatorData($survey_indicator)
    {
        return ($survey_indicator->assignee == Auth::user()->id);
    }

    public static function canFinaliseSurvey()
    {
        if (Auth::user()->isAdmin()) {
            return true;
        }

        return false;
    }

    public static function getQuestionnaireCountries($questionnaire)
    {
        return Country::select(
            'countries.name AS country_name',
            'questionnaire_countries.id AS questionnaire_country_id',
            'questionnaire_countries.default_assignee AS default_assignee',
            'questionnaire_countries.country_id AS country_id',
            'questionnaire_countries.submitted_at AS submitted_at',
            'questionnaire_countries.requested_changes_submitted_at AS requested_changes_submitted_at',
            'users1.name AS submitted_by',
            'users2.name AS approved_by'
        )
            ->leftJoin('questionnaire_countries', function ($join) use ($questionnaire) {
                $join->on('countries.id', '=', 'questionnaire_countries.country_id')->where('questionnaire_countries.questionnaire_id', $questionnaire->id);
            })
            ->leftJoin('users AS users1', 'users1.id', '=', 'questionnaire_countries.submitted_by')
            ->leftJoin('users AS users2', 'users2.id', '=', 'questionnaire_countries.approved_by')
            ->where('countries.name', '!=', config('constants.USER_GROUP'))
            ->get();
    }

    public static function getQuestionValidationRule($input, $validation, $indicator)
    {
        $rule = [];
        $validation = (isset($validation[$input])) ? $validation[$input] : null;

        if (isset($validation['required']) &&
            $validation['required'])
        {
            switch ($input)
            {
                case 'answers':
                case 'reference_year':
                case 'reference_source':
                    if ($indicator['choice'] != 3) {
                        array_push($rule, 'required');
                    }

                    break;
                case 'rating':
                    array_push($rule, 'required');

                    break;
                default:
                    break;
            }
        }
        
        if (!empty($rule)) {
            $rule = implode('|', $rule);
        }

        return $rule;
    }

    public static function getAnswers($indicator, $inputs)
    {
        $answers = [];

        foreach ($inputs as $data)
        {
            if ($indicator->id == $data['id'])
            {
                $accordion = IndicatorAccordion::getIndicatorAccordion($indicator, $data['accordion']);
                $question = IndicatorAccordionQuestion::getIndicatorAccordionQuestion($accordion, $data['order']);
                $choice = IndicatorQuestionChoice::find($data['choice']);

                array_push($answers, [
                    'question' => $question,
                    'choice' => $choice,
                    'answers' => $data['answers'],
                    'reference_year' => $data['reference_year'],
                    'reference_source' => $data['reference_source']
                ]);
            }
        }

        return $answers;
    }

    public static function getRatingAndComments($indicator, $inputs)
    {
        foreach ($inputs as $data)
        {
            if ($indicator->id == $data['id']) {
                return [$data['rating'], htmlspecialchars($data['comments'])];
            }
        }
    }

    public static function getQuestionnaireCountryData(&$questionnaire_country)
    {
        $questionnaire_country->percentage_in_progress = null;
        $questionnaire_country->percentage_approved = null;
        $questionnaire_country->status = null;
        $questionnaire_country->style = null;

        $survey_indicators = SurveyIndicator::getSurveyIndicators($questionnaire_country);

        if (!empty($survey_indicators))
        {
            $indicators = [];
            $indicators_in_progress = [];
            $indicators_processed = [];
            $indicators_approved = [];
            $indicators_final_approved = [];

            foreach ($survey_indicators as $survey_indicator)
            {
                $indicator = $survey_indicator->indicator;

                array_push($indicators, $indicator->id);

                if (in_array($survey_indicator->state_id, [3, 4, 6])) {
                    array_push($indicators_in_progress, $indicator->id);
                }

                if (($survey_indicator->state_id == 3 && $survey_indicator->assignee == $questionnaire_country->default_assignee) ||
                    $survey_indicator->state_id > 6)
                {
                    array_push($indicators_processed, $indicator->id);
                    array_push($indicators_approved, $indicator->id);

                    if ($survey_indicator->state_id == 8) {
                        array_push($indicators_final_approved, $indicator->id);
                    }
                }
            }

            $questionnaire_country->indicators = count($indicators);
            $questionnaire_country->indicators_processed = count($indicators_processed);
            $questionnaire_country->indicators_in_progress = count($indicators_in_progress);
            $questionnaire_country->indicators_approved = count($indicators_approved);
            $questionnaire_country->indicators_final_approved = count($indicators_final_approved);
            $questionnaire_country->percentage_in_progress = ($questionnaire_country->indicators) ? (int) round(($questionnaire_country->indicators_in_progress / $questionnaire_country->indicators) * 100) : 0;
            $questionnaire_country->percentage_approved = ($questionnaire_country->indicators) ? (int) round(($questionnaire_country->indicators_approved / $questionnaire_country->indicators) * 100) : 0;
            $questionnaire_country->percentage_processed = ($questionnaire_country->indicators) ? round(($questionnaire_country->indicators_processed / $questionnaire_country->indicators) * 100) : 0;
            $questionnaire_country->percentage_final_approved = ($questionnaire_country->indicators) ? round(($questionnaire_country->indicators_final_approved / $questionnaire_country->indicators) * 100) : 0;
            $default_assignee = User::withTrashed()->find($questionnaire_country->default_assignee);
            $questionnaire_country->primary_poc = null;
            if (!is_null($default_assignee)) {
                $questionnaire_country->primary_poc = $default_assignee->name . ($default_assignee->trashed() ? ' ' . config('constants.USER_INACTIVE') : '');
            }
            if ($questionnaire_country->approved_by)
            {
                $questionnaire_country->status = 'Approved';
                $questionnaire_country->style = 'positive-with-tooltip';
                $questionnaire_country->info = 'Survey has been approved by ' . config('constants.USER_GROUP') . ' and is considered closed for this year.';
            }
            elseif ($questionnaire_country->submitted_by)
            {
                $questionnaire_country->status = 'Submitted';
                $questionnaire_country->style = 'approved-with-tooltip';
                $questionnaire_country->info = 'Survey has been submitted by the MS and is under review by ' . config('constants.USER_GROUP') . '. Clarifications or changes may be requested.';
            }
            elseif ($questionnaire_country->percentage_processed)
            {
                $questionnaire_country->status = 'In progress';
                $questionnaire_country->style = 'positive-invert-with-tooltip';
                $questionnaire_country->info = 'Survey completion or revision following request by ' . config('constants.USER_GROUP') . ' is in progress.';
            }
        }

        if (!is_null($questionnaire_country->questionnaire_country_id)) {
            $questionnaire_country->id = $questionnaire_country->questionnaire_country_id;
        }

        $latest_requested_changes = IndicatorRequestedChange::getLatestQuestionnaireCountryRequestedChanges($questionnaire_country, [], 'deadline');
        if (!is_null($latest_requested_changes)) {
            $questionnaire_country->requested_changes_deadline = $latest_requested_changes->deadline;
        }
    }

    public static function getQuestionnaireCountryDataNotAvailable($survey_indicators)
    {
        $data_not_available = [];

        foreach ($survey_indicators as $survey_indicator)
        {
            $indicator = $survey_indicator->indicator;
            $answers = $survey_indicator->answers()->get();

            $number = 0;

            foreach ($answers as $answer)
            {
                $number++;

                $question = $answer->question()->first();

                if ($answer->choice_id == 3) {
                    array_push($data_not_available, [
                        'order' => $indicator->order,
                        'title' => $indicator->name,
                        'number' => $number,
                        'question' => $question->title
                    ]);
                }
            }
        }

        return $data_not_available;
    }

    public static function getQuestionnaireCountryDataReferences($survey_indicators)
    {
        $references = [];

        foreach ($survey_indicators as $survey_indicator)
        {
            $indicator = $survey_indicator->indicator;
            $answers = $survey_indicator->answers()->get();
            
            $number = 0;

            foreach ($answers as $answer)
            {
                $number++;

                if (!is_null($answer->reference_year) ||
                    !empty($answer->reference_source))
                {
                    array_push($references, [
                        'order' => $indicator->order,
                        'title' => $indicator->name,
                        'number' => $number,
                        'reference_year' => $answer->reference_year,
                        'reference_source' => $answer->reference_source
                    ]);
                }
            }
        }

        return $references;
    }

    public static function getQuestionnaireCountryDataComments($survey_indicators)
    {
        $comments = [];

        foreach ($survey_indicators as $survey_indicator)
        {
            $indicator = $survey_indicator->indicator;

            if (!empty($survey_indicator->comments)) {
                array_push($comments, [
                    'order' => $indicator->order,
                    'title' => $indicator->name,
                    'comments' => $survey_indicator->comments
                ]);
            }
        }

        return $comments;
    }

    public static function getAssigneeQuestionnairesCountryData($questionnaire_country_id = null)
    {
        $questionnaires = UserPermissions::getUserQuestionnaires($questionnaire_country_id);
        $data = [];

        foreach ($questionnaires as $questionnaire)
        {
            $questionnaire_country = QuestionnaireCountry::find($questionnaire->questionnaire_country_id);
            $survey_indicators = SurveyIndicator::getSurveyIndicators($questionnaire_country);
            
            if (!empty($survey_indicators))
            {
                if ($questionnaire->submitted_by)
                {
                    $submitted_by = User::withTrashed()->where('name', $questionnaire->submitted_by)->first();
                    $questionnaire->submitted_by .= ($submitted_by->trashed() ? ' ' . config('constants.USER_INACTIVE') : '');
                }

                $indicators_data = self::getSurveyIndicatorsData($questionnaire, $survey_indicators);
                
                array_push($data, [
                    'questionnaire' => $questionnaire,
                    'questionnaire_started' => $indicators_data['started'],
                    'indicators_percentage' => $indicators_data['percentage'],
                    'indicators_assigned' => $indicators_data['assigned'],
                    'indicators_assigned_exact' => $indicators_data['assigned_exact'],
                    'indicators_submitted' => $indicators_data['submitted'],
                    'indicators_approved' => $indicators_data['approved']
                ]);
            }
        }

        return (!is_null($questionnaire_country_id) && !empty($data)) ? $data[0] : $data;
    }

    public static function getAssigneeQuestionnairesCountrySummaryData()
    {
        $data = self::getAssigneeQuestionnairesCountryData();
        $questionnaires = [];
        $questionnaires_assigned = [];

        foreach ($data as $questionnaire_country_data)
        {
            $questionnaire = $questionnaire_country_data['questionnaire'];
            $questionnaire->indicators_assigned = $questionnaire_country_data['indicators_assigned'];
            $questionnaire->indicators_assigned_exact = (!empty($questionnaire_country_data['indicators_assigned_exact'])) ? true : false;
            $questionnaire->indicators_submitted = $questionnaire_country_data['indicators_submitted'];

            if (!empty($questionnaire->indicators_assigned)) {
                array_push($questionnaires_assigned, $questionnaire);
            }

            array_push($questionnaires, $questionnaire);
        }

        return [
            'questionnaires' => $questionnaires,
            'questionnaires_assigned' => $questionnaires_assigned
        ];
    }

    public static function getQuestionnaireCountryIndicators($questionnaire)
    {
        $survey_indicators = SurveyIndicator::getSurveyIndicators($questionnaire);
        $indicators = [];
        
        if (!empty($survey_indicators))
        {
            foreach ($survey_indicators as $survey_indicator)
            {
                $indicator = $survey_indicator->indicator;
                            
                $assignee = ($survey_indicator->assignee) ? User::withTrashed()->find($survey_indicator->assignee) : '';
                $assignee_name = '';
                $assignee_info = '';
                if ($assignee)
                {
                    $assignee_name = $assignee->name;
                    if ($assignee->trashed()) {
                        $assignee_info = config('constants.USER_INACTIVE');
                    }
                    elseif ($assignee->name == Auth::user()->name) {
                        $assignee_info = '(you)';
                    }
                }
                $requested_changes_indicator = IndicatorRequestedChange::getIndicatorRequestedChanges($questionnaire, $indicator);
                    
                array_push($indicators, [
                    'id' => $indicator->id,
                    'identifier' => $indicator->identifier,
                    'number' => $indicator->order,
                    'name' => $indicator->name,
                    'state' => $survey_indicator->state_id,
                    'dashboard_state' => $survey_indicator->dashboard_state_id,
                    'default_assignee' => $questionnaire->default_assignee,
                    'assignee' => [
                        'id' => ($assignee) ? $assignee->id : '',
                        'name' => $assignee_name . (!empty($assignee_info) ? ' ' . $assignee_info : '')
                    ],
                    'deadline' => GeneralHelper::dateFormat($survey_indicator->deadline, 'd-m-Y'),
                    'requested_changes' => ($requested_changes_indicator->count()) ? true : false,
                    'questionnaire_country' => [
                        'id' => $questionnaire->id,
                        'submitted_by' => $questionnaire->submitted_by,
                        'approved_by' => $questionnaire->approved_by
                    ]
                ]);
            }
        }

        return $indicators;
    }

    public static function getSurveyIndicatorsData($questionnaire, $survey_indicators)
    {
        $indicators = [];
        $processed = [];
        $assigned = [
            'id' => [],
            'identifier' => []
        ];
        $assigned_exact = [
            'id' => [],
            'identifier' => []
        ];
        $started = false;
        $completed = true;
        $submitted = true;
        $approved = true;

        foreach ($survey_indicators as $survey_indicator)
        {
            $indicator = $survey_indicator->indicator;

            array_push($indicators, $indicator->id);

            if (($survey_indicator->state_id == 3 && $survey_indicator->assignee == $questionnaire->default_assignee) ||
                $survey_indicator->state_id > 6)
            {
                array_push($processed, $indicator->id);
            }

            if ($survey_indicator->assignee != $questionnaire->default_assignee) {
                $completed &= (in_array($survey_indicator->state_id, [4, 7, 8]));
            }
            $approved &= ($survey_indicator->state_id == 8);

            // Remove indicator from indicators assigned list
            if (!Auth::user()->isAdmin() &&                      // User is not admin
                !Auth::user()->isPoC() &&                        // User is not PoC
                $survey_indicator->assignee != Auth::user()->id) // User is not assigned this indicator
            {
                continue;
            }

            $pending_requested_changes_indicator = IndicatorRequestedChange::getPendingIndicatorRequestedChanges($questionnaire, $indicator);
            
            $started |= ((!is_null($pending_requested_changes_indicator) && $pending_requested_changes_indicator->requested_by == Auth::user()->id) ||
                         ($survey_indicator->approved_by == Auth::user()->id) ||
                         ($survey_indicator->assignee == Auth::user()->id && in_array($survey_indicator->state_id, [3, 6])));

            array_push($assigned['id'], $indicator->id);
            array_push($assigned['identifier'], $indicator->identifier);
            if ($survey_indicator->assignee == Auth::user()->id)
            {
                $submitted &= (in_array($survey_indicator->state_id, [4, 7, 8]));

                array_push($assigned_exact['id'], $indicator->id);
                array_push($assigned_exact['identifier'], $indicator->identifier);
            }
        }

        $is_assigned_exact = (!empty($assigned_exact['id']) && !empty($assigned_exact['identifier'])) ? true : false;

        return [
            'indicators' => $indicators,
            'percentage' => (count($indicators)) ? round((count($processed) / count($indicators)) * 100) : 0,
            'assigned' => (!empty($assigned['id']) && !empty($assigned['identifier'])) ? $assigned : [],
            'assigned_exact' => ($is_assigned_exact) ? $assigned_exact : [],
            'started' => $started,
            'completed' => $completed,
            'submitted' => ($is_assigned_exact && $submitted) ? true : false,
            'approved' => $approved
        ];
    }

    public static function getLastPublishedSurveyIndicatorData($questionnaire, $indicator)
    {
        $last_published_questionnaire = Questionnaire::getLastPublishedQuestionnaire();
        $last_questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $last_published_questionnaire->id)->where('country_id', $questionnaire->country_id)->first();
        $last_indicator = Indicator::getLastIndicator($indicator->year, $indicator->identifier);

        return SurveyIndicator::getSurveyIndicator($last_questionnaire_country, $last_indicator);
    }

    public static function editIndicator(&$questionnaire, &$survey_indicator, &$data, $inputs, &$audit, &$resp)
    {
        $author = Auth::user();
        $indicator = $survey_indicator->indicator;
        $indicator_title = $indicator->order . '. ' . $indicator->name;
        $latest_requested_changes_indicator = IndicatorRequestedChange::getLatestIndicatorRequestedChanges($questionnaire, $indicator);

        $can_update = (in_array($survey_indicator->state_id, [1, 2, 3, 4, 6]));

        if (!$can_update)
        {
            $resp = [
                'type' => 'error',
                'msg' => self::ERROR_NOT_ALLOWED,
                'status' => 405
            ];

            return false;
        }

        $new_assignee = (int)$inputs['assignee'];
        $new_deadline = $inputs['deadline'];

        $assignee_updated = ($survey_indicator->assignee != $new_assignee) ? true : false;
        $deadline_updated = ($survey_indicator->deadline != $new_deadline) ? true : false;

        // Reset indicator when assignee changes
        if ($assignee_updated)
        {
            if ($new_assignee != $questionnaire->default_assignee) {
                $questionnaire->completed = false;
            }
            
            $survey_indicator->state_id = $survey_indicator->dashboard_state_id = 2;

            if (!is_null($latest_requested_changes_indicator) &&
                is_null($latest_requested_changes_indicator->answered_at))
            {
                $latest_requested_changes_indicator->requested_to = $new_assignee;
                $latest_requested_changes_indicator->save();

                $survey_indicator->state_id = 6;
            }

            if (isset($inputs['reset-answers']) &&
                $inputs['reset-answers'])
            {
                $survey_indicator->rating = null;
                $survey_indicator->comments = null;
                $survey_indicator->last_saved = null;

                $survey_indicator->answers()->delete();
                $survey_indicator->options()->delete();
            }

            // Notify user except default assignee
            if (($author->id == $questionnaire->default_assignee &&
                 $survey_indicator->assignee != $questionnaire->default_assignee) ||
                $author->id != $questionnaire->default_assignee)
            {
                $user = User::withTrashed()->find($survey_indicator->assignee);
                if (!$user->trashed())
                {
                    if (!isset($data['reassigned_indicators'][$survey_indicator->assignee])) {
                        $data['reassigned_indicators'][$survey_indicator->assignee] = [];
                    }

                    array_push($data['reassigned_indicators'][$survey_indicator->assignee], $indicator_title);
                }
            }
        }

        if ($assignee_updated ||
            ($deadline_updated && in_array($survey_indicator->state_id, [1, 2, 3, 6])))
        {
            // Notify user except default assignee
            if (($author->id == $questionnaire->default_assignee &&
                 $new_assignee != $questionnaire->default_assignee) ||
                $author->id != $questionnaire->default_assignee)
            {
                if (!isset($data['assigned_indicators'][$new_assignee])) {
                    $data['assigned_indicators'][$new_assignee] = [];
                }

                array_push($data['assigned_indicators'][$new_assignee], $indicator_title);
            }
        }

        $survey_indicator->assignee = $new_assignee;
        $survey_indicator->deadline = GeneralHelper::dateFormat($new_deadline, 'Y-m-d');

        if ($deadline_updated ||
            $assignee_updated)
        {
            $audit['status'] = 'Updated';
            $audit['indicator'] = $indicator->order;
            $audit['deadline'] = $new_deadline;
            $audit['assignee'] = User::find($new_assignee)->name;
        }

        return true;
    }

    public static function saveIndicator($questionnaire, &$survey_indicator, $inputs, &$audit, &$resp)
    {
        $author = Auth::user();
        $indicator = $survey_indicator->indicator;
        $pending_requested_changes_indicator = IndicatorRequestedChange::getPendingIndicatorRequestedChanges($questionnaire, $indicator);

        $can_update = ($survey_indicator->assignee == $author->id && in_array($survey_indicator->state_id, [2, 3, 6]));

        if (!$can_update)
        {
            $resp = [
                'type' => 'error',
                'msg' => self::ERROR_NOT_ALLOWED,
                'status' => 405
            ];

            return false;
        }

        if ($survey_indicator->state_id == 6 &&
            !is_null($pending_requested_changes_indicator))
        {
            $resp = [
                'type' => 'warning',
                'msg' => 'Indicator cannot be updated as there are requested changes that have not been submitted yet.',
                'status' => 405
            ];

            return false;
        }
        
        self::updateSurveyIndicatorAnswers($survey_indicator, $inputs['indicator_answers']);
        
        $audit['status'] = 'Saved';
        $audit['indicator'] = $indicator->order;
        $audit['data'] = $inputs['indicator_answers'];

        return true;
    }

    public static function submitIndicator($questionnaire, &$survey_indicator, &$resp)
    {
        $author = Auth::user();
        $indicator = $survey_indicator->indicator;
        $pending_requested_changes_indicator = IndicatorRequestedChange::getPendingIndicatorRequestedChanges($questionnaire, $indicator);

        if ($survey_indicator->assignee == $author->id)
        {
            if ($survey_indicator->state_id == 6 &&
                !is_null($pending_requested_changes_indicator))
            {
                $resp = [
                    'type' => 'warning',
                    'msg' => 'Survey cannot be submitted as there are requested changes that have not been submitted yet.',
                    'status' => 405
                ];

                return false;
            }

            $can_update = (in_array($survey_indicator->state_id, [2, 3, 4, 6]));

            if ($can_update)
            {
                $survey_indicator->state_id = $survey_indicator->dashboard_state_id = ($questionnaire->default_assignee == $author->id) ? 7 : 4;
                $survey_indicator->answers_loaded = false;

                if ($questionnaire->default_assignee == $author->id) {
                    $survey_indicator->approved_by = $author->id;
                }
            }
        }

        return true;
    }

    public static function requestChangesQuestionnaireCountryIndicator($questionnaire, $indicator, $inputs)
    {
        $author = Auth::user();
        $survey_indicator = SurveyIndicator::getSurveyIndicator($questionnaire, $indicator);
        $default_assignee = $questionnaire->default_assignee;
        $indicator_assignee = $survey_indicator->assignee;
        $pending_requested_changes_indicator = IndicatorRequestedChange::getPendingIndicatorRequestedChanges($questionnaire, $indicator);
        $pending_requested_changes_indicator_author_role = (!is_null($pending_requested_changes_indicator)) ? $pending_requested_changes_indicator->user_requested_by->permissions()->first()->role->id : '';
        $is_assigned = ($indicator_assignee == $author->id) ? true : false;
        
        $can_request_changes = false;
        if (Auth::user()->isAdmin()) {
            $can_request_changes |= (($survey_indicator->state_id == 6 && !is_null($pending_requested_changes_indicator) && $pending_requested_changes_indicator_author_role == 1 ||
                                      $survey_indicator->state_id == 7) &&
                                     !is_null($questionnaire->submitted_by) &&
                                     !$is_assigned);
        }
        elseif (Auth::user()->isPoC())
        {
            if (Auth::user()->isPrimaryPoC()) {
                $can_request_changes |= ((($survey_indicator->state_id == 6 && !is_null($pending_requested_changes_indicator) && $pending_requested_changes_indicator_author_role == 5) ||
                                          $survey_indicator->state_id == 4) &&
                                         $questionnaire->completed &&
                                         !$is_assigned);
            }
            else {
                $can_request_changes |= ((($survey_indicator->state_id == 6 && !is_null($pending_requested_changes_indicator) && $pending_requested_changes_indicator_author_role == 2) ||
                                          $survey_indicator->state_id == 4) &&
                                         !$is_assigned);
            }
        }

        if (!$can_request_changes)
        {
            return [
                'type' => 'error',
                'msg' => 'Changes cannot be requested as the requested action is not allowed!',
                'status' => 405
            ];
        }
        
        if (Auth::user()->isAdmin()) {
            $indicator_assignee = $default_assignee;
        }

        $user = User::withTrashed()->find($indicator_assignee);

        if ($user->trashed())
        {
            return [
                'type' => 'warning',
                'msg' => 'Request changes are not allowed as ' . (Auth::user()->isAdmin() ? 'Primary PoC' : 'indicator assignee') . ' is inactive.' . (Auth::user()->isAdmin() ? '' : ' Please re-assign indicator to an active user.'),
                'status' => 405
            ];
        }

        $new_deadline = $inputs['deadline'];
        $new_changes = urldecode($inputs['changes']);
        
        $deadline_updated = (is_null($pending_requested_changes_indicator) ||
                            (!is_null($pending_requested_changes_indicator) &&
                             $pending_requested_changes_indicator->deadline != $new_deadline));
        $changes_updated = (is_null($pending_requested_changes_indicator) ||
                            (!is_null($pending_requested_changes_indicator) &&
                             $pending_requested_changes_indicator->changes != $new_changes));

        $data = [
            'questionnaire_country_id' => $questionnaire->id,
            'indicator_id' => $indicator->id,
            'changes' => $new_changes,
            'deadline' => GeneralHelper::dateFormat($new_deadline, 'Y-m-d'),
            'requested_by' => Auth::user()->id,
            'requested_to' => $user->id,
            'requested_at' => Carbon::now(),
            'state' => 1
        ];
        if (!is_null($pending_requested_changes_indicator)) {
            $data['id'] = $pending_requested_changes_indicator->id;
        }
        else
        {
            $data['indicator_previous_state'] = $survey_indicator->state_id;
            $data['indicator_previous_assignee'] = $survey_indicator->assignee;
        }
                    
        IndicatorRequestedChange::updateOrCreateRequestedChanges($data);

        // Reset indicator assignee to default assignee
        if (Auth::user()->isAdmin()) {
            $survey_indicator->assignee = $indicator_assignee;
        }
        $survey_indicator->state_id = 6;
        $survey_indicator->dashboard_state_id = 5;
        $survey_indicator->deadline = GeneralHelper::dateFormat($new_deadline, 'Y-m-d');
        $survey_indicator->approved_by = null;

        SurveyIndicator::saveSurveyIndicator($survey_indicator);

        if ($deadline_updated ||
            $changes_updated)
        {
            Audit::setCustomAuditEvent(
                QuestionnaireCountry::find($questionnaire->id),
                ['event' => 'updated', 'audit' => [
                    'status' => 'Requested Changes',
                    'deadline' => $new_deadline,
                    'assignee' => $user->name,
                    'changes' => $new_changes
                ]]
            );
        }

        return [
            'type' => 'success',
            'msg' => 'Indicator has been successfully requested changes!'
        ];
    }

    public static function discardRequestedChangesQuestionnaireCountryIndicator($questionnaire, $indicator)
    {
        $author = Auth::user();
        $survey_indicator = SurveyIndicator::getSurveyIndicator($questionnaire, $indicator);
        $indicator_assignee = $survey_indicator->assignee;
        $pending_requested_changes_indicator = IndicatorRequestedChange::getPendingIndicatorRequestedChanges($questionnaire, $indicator);
        $pending_requested_changes_indicator_author_role = $pending_requested_changes_indicator->user_requested_by->permissions()->first()->role->id;
        $is_assigned = ($indicator_assignee == $author->id) ? true : false;

        $can_discard = false;
        if (Auth::user()->isAdmin()) {
            $can_discard |= ($survey_indicator->state_id == 6 && !is_null($pending_requested_changes_indicator) && $pending_requested_changes_indicator_author_role == 1 &&
                             !is_null($questionnaire->submitted_by) &&
                             !$is_assigned);
        }
        elseif (Auth::user()->isPoC())
        {
            if (Auth::user()->isPrimaryPoC()) {
                $can_discard |= ($survey_indicator->state_id == 6 && !is_null($pending_requested_changes_indicator) && $pending_requested_changes_indicator_author_role == 5 &&
                                 $questionnaire->completed &&
                                 !$is_assigned);
            }
            else {
                $can_discard |= ($survey_indicator->state_id == 6 && !is_null($pending_requested_changes_indicator) && $pending_requested_changes_indicator_author_role == 2 &&
                                 !$is_assigned);
            }
        }

        if (!$can_discard)
        {
            return [
                'type' => 'error',
                'msg' => 'Requested changes cannot be discarded as the requested action is not allowed!',
                'status' => 405
            ];
        }
                    
        IndicatorRequestedChange::discardRequestedChanges($pending_requested_changes_indicator);

        $survey_indicator->state_id = $survey_indicator->dashboard_state_id = $pending_requested_changes_indicator->indicator_previous_state;
        $survey_indicator->assignee = $pending_requested_changes_indicator->indicator_previous_assignee;

        SurveyIndicator::saveSurveyIndicator($survey_indicator);

        return [
            'type' => 'success',
            'msg' => 'Indicator requested changes have been successfully discarded!'
        ];
    }

    public static function submitQuestionnaireCountryRequestedChanges($questionnaire)
    {
        $pending_requested_changes = IndicatorRequestedChange::getQuestionnaireCountryRequestedChanges($questionnaire, [1]);
        
        foreach($pending_requested_changes as $pending_requested_change)
        {
            IndicatorRequestedChange::updateOrCreateRequestedChanges([
                'id' => $pending_requested_change->id,
                'state' => 2
            ]);

            $survey_indicator = SurveyIndicator::getSurveyIndicator($questionnaire, $pending_requested_change->indicator);
            $survey_indicator->dashboard_state_id = 6;

            SurveyIndicator::saveSurveyIndicator($survey_indicator);
        }

        return $pending_requested_changes;
    }

    public static function approveIndicator(&$survey_indicator, &$audit, &$resp)
    {
        $author = Auth::user();

        $can_update = ($survey_indicator->state_id == 4);

        if (!$can_update)
        {
            $resp = [
                'type' => 'error',
                'msg' => self::ERROR_NOT_ALLOWED,
                'status' => 405
            ];

            return false;
        }

        $survey_indicator->state_id = $survey_indicator->dashboard_state_id = 7;
        $survey_indicator->approved_by = $author->id;

        $user = User::withTrashed()->find($survey_indicator->assignee);

        $audit['status'] = 'Approved';
        $audit['assignee'] = $user->name;

        return true;
    }

    public static function finalApproveIndicator($questionnaire, &$survey_indicator, &$audit, &$resp)
    {
        $author = Auth::user();

        $can_update = (!is_null($questionnaire->submitted_by) && $survey_indicator->state_id == 7);

        if (!$can_update)
        {
            $resp = [
                'type' => 'error',
                'msg' => self::ERROR_NOT_ALLOWED,
                'status' => 405
            ];

            return false;
        }

        $survey_indicator->state_id = $survey_indicator->dashboard_state_id = 8;
        $survey_indicator->approved_by = $author->id;

        $user = User::withTrashed()->find($survey_indicator->assignee);

        $audit['status'] = 'Approved';
        $audit['assignee'] = $user->name;

        return true;
    }

    public static function unapproveIndicator(&$survey_indicator, &$audit, &$resp)
    {
        $can_update = ($survey_indicator->state_id == 8);

        if (!$can_update)
        {
            $resp = [
                'type' => 'error',
                'msg' => self::ERROR_NOT_ALLOWED,
                'status' => 405
            ];

            return false;
        }

        $survey_indicator->state_id = $survey_indicator->dashboard_state_id = 7;

        $audit['status'] = 'Unapproved';

        return true;
    }

    public static function updateSurveyIndicatorsData($questionnaire, $indicators, $inputs)
    {
        $resp = null;
        $data = [];
        
        foreach ($indicators as $indicator)
        {
            $survey_indicator = SurveyIndicator::getSurveyIndicator($questionnaire, $indicator);
            
            $audit = [
                'status' => null,
                'indicator' => $indicator->order
            ];
                        
            if ($inputs['action'] == 'edit') {
                self::editIndicator($questionnaire, $survey_indicator, $data, $inputs, $audit, $resp);
            }
            elseif ($inputs['action'] == 'save') {
                self::saveIndicator($questionnaire, $survey_indicator, $inputs, $audit, $resp);
            }
            elseif ($inputs['action'] == 'submit') {
                self::submitIndicator($questionnaire, $survey_indicator, $resp);
            }
            elseif ($inputs['action'] == 'approve') {
                self::approveIndicator($survey_indicator, $audit, $resp);
            }
            elseif ($inputs['action'] == 'final_approve') {
                self::finalApproveIndicator($questionnaire, $survey_indicator, $audit, $resp);
            }
            elseif ($inputs['action'] == 'unapprove') {
                self::unapproveIndicator($survey_indicator, $audit, $resp);
            }

            if (!is_null($resp)) {
                return $resp;
            }

            SurveyIndicator::saveSurveyIndicator($survey_indicator);

            if (!is_null($audit['status'])) {
                Audit::setCustomAuditEvent(
                    QuestionnaireCountry::find($questionnaire->id),
                    ['event' => 'updated', 'audit' => $audit]
                );
            }
        }

        QuestionnaireCountry::saveQuestionnaireCountry($questionnaire);

        return [
            'type' => 'success',
            'msg' => 'Indicators have been successfully updated!',
            'data' => $data
        ];
    }

    public static function updateSurveyIndicatorAnswers(&$survey_indicator, $inputs)
    {
        SurveyIndicatorAnswer::disableAuditing();
        SurveyIndicatorOption::disableAuditing();

        if ($survey_indicator->state_id == 2) {
            $survey_indicator->state_id = 3;
        }

        $survey_indicator->dashboard_state_id = 3;

        $last_saved = date('Y-m-d') . ' ' . date('H:i:s');

        $indicator = $survey_indicator->indicator;

        $answers = self::getAnswers($indicator, $inputs);
        foreach ($answers as $answer_data)
        {
            $new_answer = false;

            $question_id = $answer_data['question']->id;
            $question_type = $answer_data['question']->type_id;
            $free_text = ($question_type == 3 && !empty($answer_data['answers'])) ? $answer_data['answers'][0] : null;
            $reference_year = $answer_data['reference_year'];
            $reference_source = $answer_data['reference_source'];
            
            $db_answer = SurveyIndicatorAnswer::getSurveyIndicatorAnswer(
                $survey_indicator,
                $answer_data['question'],
                $answer_data['choice'],
                $free_text,
                $reference_year,
                $reference_source
            );
            
            if (is_null($db_answer))
            {
                SurveyIndicatorAnswer::updateOrCreateSurveyIndicatorAnswer(
                    [
                        'survey_indicator_id' => $survey_indicator->id,
                        'question_id' => $question_id,
                        'choice_id' => $answer_data['choice']->id,
                        'free_text' => $free_text,
                        'reference_year' => $reference_year,
                        'reference_source' => $reference_source,
                        'last_saved' => $last_saved
                    ]
                );

                $new_answer |= true;
            }

            if ($question_type == 1 ||
                $question_type == 2)
            {
                $options = IndicatorAccordionQuestionOption::getIndicatorAccordionQuestionOptions($answer_data['question']);
                foreach ($options as $option)
                {
                    $db_option = SurveyIndicatorOption::getSurveyIndicatorOption($survey_indicator, $option);

                    if (in_array($option->value, $answer_data['answers']))
                    {
                        if (is_null($db_option))
                        {
                            SurveyIndicatorOption::create(
                                [
                                    'survey_indicator_id' => $survey_indicator->id,
                                    'option_id' => $option->id,
                                    'last_saved' => $last_saved
                                ]
                            );

                            $new_answer |= true;
                        }
                    }
                    elseif (!is_null($db_option))
                    {
                        SurveyIndicatorOption::deleteSurveyIndicatorOption($survey_indicator, $option);

                        $new_answer |= true;
                    }
                }
            }
            
            if ($new_answer ||
                $survey_indicator->answers_loaded)
            {
                SurveyIndicatorAnswer::updateOrCreateSurveyIndicatorAnswer(
                    [
                        'survey_indicator_id' => $survey_indicator->id,
                        'question_id' => $question_id,
                        'last_saved' => $last_saved
                    ]
                );
            }
        }

        [$survey_indicator->rating, $survey_indicator->comments] = self::getRatingAndComments($indicator, $inputs);
        $survey_indicator->last_saved = $last_saved;
        $survey_indicator->answers_loaded = false;

        SurveyIndicatorAnswer::enableAuditing();
        SurveyIndicatorOption::enableAuditing();
    }

    public static function notifyUserForIndicators($questionnaire, $inputs, $resp)
    {
        if (isset($resp['data']['assigned_indicators']))
        {
            foreach ($resp['data']['assigned_indicators'] as $assignee => $indicators)
            {
                $user = User::find($assignee);
                $user->notify(new NotifyUser([
                    'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
                    'subject' => 'EU Cybersecurity Index Survey - Indicator Assignment',
                    'markdown' => 'questionnaire-indicators-assignment-add',
                    'maildata' => [
                        'url' => env('APP_URL') . '/questionnaire/management',
                        'questionnaire' => $questionnaire->questionnaire->title,
                        'indicators' => $indicators,
                        'author' => Auth::user()->name,
                        'deadline' => $inputs['deadline']
                    ]
                ]));
            }
        }

        if (isset($resp['data']['reassigned_indicators']))
        {
            foreach ($resp['data']['reassigned_indicators'] as $assignee => $indicators)
            {
                $user = User::find($assignee);
                $user->notify(new NotifyUser([
                    'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
                    'subject' => 'EU Cybersecurity Index Survey - Indicator Delegation',
                    'markdown' => 'questionnaire-indicators-assignment-remove',
                    'maildata' => [
                        'questionnaire' => $questionnaire->questionnaire->title,
                        'country' => $questionnaire->country->name,
                        'indicators' => $indicators,
                        'author' => Auth::user()->name
                    ]
                ]));
            }
        }
    }

    public static function getImportSpreadsheetInputs($sheets, $questionnaire, $indicators_assigned)
    {
        $data = [];
        $comments = [];
        $rating = [];
        $single_choice_options = [];
        $inputs = [];

        foreach ($sheets as $sheet)
        {
            $title = $sheet->getTitle();

            if (!is_numeric($title)) {
                continue;
            }

            $rows = $sheet->toArray();

            $identifier = null;
            $question_id = null;
            $accordion = null;
            $order = null;
            $count = 0;
            
            foreach ($rows as $row)
            {
                if (is_null($identifier))
                {
                    $identifier = $row[3];

                    if (!in_array($identifier, $indicators_assigned)) {
                        break;
                    }

                    $indicator = Indicator::where('identifier', $identifier)->where('year', $questionnaire->questionnaire->year)->first();
                }

                if (isset($row[25]))
                {
                    if (!isset($single_choice_options[$row[25]])) {
                        $single_choice_options[$row[25]] = [];
                    }

                    array_push($single_choice_options[$row[25]], $row[24]);
                }

                if (preg_match('/^choice/', $row[3]))
                {
                    $parts = explode('-', $row[3]);
                    $accordion = $parts[1];
                    $order = $parts[2];

                    $question_id = $identifier . '-' . $accordion . '-' . $order;

                    if (!isset($data[$question_id]))
                    {
                        $count = 0;

                        $data[$question_id] = [
                            'id' => $indicator->id,
                            'accordion' => $accordion,
                            'order' => $order,
                            'choice' => '',
                            'answers' => [],
                            'reference_year' => '',
                            'reference_source' => ''
                        ];
                    }

                    if ($row[2] == 'Choose answer') {
                        $data[$question_id]['choice'] = 1;
                    }
                    elseif ($row[2] == 'Provide your answer') {
                        $data[$question_id]['choice'] = 2;
                    }
                    elseif ($row[2] == 'Data not available/Not willing to share') {
                        $data[$question_id]['choice'] = 3;
                    }

                    continue;
                }

                if (preg_match('/option/', $row[4]))
                {
                    $count++;

                    $question_id = self::getQuestionId($identifier, $row[4]);

                    if ($row[3] == 'single-choice' ||
                        $row[3] == 'free-text')
                    {
                        if (!is_null($row[2])) {
                            array_push($data[$question_id]['answers'], $row[2]);
                        }
                    }
                    elseif ($row[3] == 'multiple-choice')
                    {
                        if ($row[2] == 'Yes') {
                            array_push($data[$question_id]['answers'], $count);
                        }
                    }

                    continue;
                }

                if (preg_match('/reference_year/', $row[3]))
                {
                    $question_id = self::getQuestionId($identifier, $row[3]);

                    $data[$question_id]['reference_year'] = $row[1];

                    continue;
                }

                if (preg_match('/reference_source/', $row[3]))
                {
                    $question_id = self::getQuestionId($identifier, $row[3]);

                    $data[$question_id]['reference_source'] = $row[1];

                    continue;
                }

                if ($row[0] == 'Comments')
                {
                    $comments[$identifier] = $row[1];

                    continue;
                }

                if ($row[0] == 'Rating') {
                    $rating[$identifier] = $row[1];
                }
            }
        }

        foreach ($single_choice_options as $question_id => $options)
        {
            if (!empty($data[$question_id]['answers'])) {
                $data[$question_id]['answers'][0] = array_search($data[$question_id]['answers'][0], $options) + 1;
            }
        }

        foreach ($data as $question_id => $question_data)
        {
            $parts = explode('-', $question_id);

            $question_data['comments'] = $comments[$parts[0]];
            $question_data['rating'] = $rating[$parts[0]];

            array_push($inputs, $question_data);
        }

        return $inputs;
    }
    
    public static function getQuestionId($identifier, $data)
    {
        $parts = explode('-', $data);
        $accordion = $parts[1];
        $order = $parts[2];

        return $identifier . '-' . $accordion . '-' . $order;
    }

    public static function getProcessedIndicators($questionnaire, $indicators)
    {
        $survey_indicators = SurveyIndicator::getSurveyIndicators($questionnaire);
        
        $indicatorsWithValues = [];
        foreach ($indicators as $indicator)
        {
            foreach ($survey_indicators as $survey_indicator)
            {
                if ($indicator->id == $survey_indicator->indicator_id)
                {
                    if (in_array($survey_indicator->state_id, [3, 4, 6, 7])) {
                        array_push($indicatorsWithValues, $indicator);
                    }

                    break;
                }
            }
        }

        return $indicatorsWithValues;
    }

    public static function questionnaireUploadFile($excel, $questionnaire, $originalName, $explicit)
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($excel);

        $sheets = $spreadsheet->getAllSheets();

        $identifiers = [];
        foreach ($sheets as $sheet)
        {
            $title = $sheet->getTitle();

            if (!is_numeric($title)) {
                continue;
            }

            $rows = $sheet->toArray();

            $identifier = (isset($rows[0][3])) ? $rows[0][3] : null;

            if (!is_null($identifier)) {
                array_push($identifiers, $identifier);
            }
        }
        sort($identifiers);

        $data = self::getAssigneeQuestionnairesCountryData($questionnaire->id);
        sort($data['indicators_assigned_exact']['identifier']);
        
        $skipIndicatorNames = [];
        if (array_map('intval', $identifiers) !== $data['indicators_assigned_exact']['identifier'])
        {
            $indicators_diff = array_diff(array_map('intval', $identifiers), $data['indicators_assigned_exact']['identifier']);

            foreach ($indicators_diff as $identifier)
            {
                $indicator = Indicator::where('identifier', $identifier)->where('year', $questionnaire->questionnaire->year)->first();

                array_push($skipIndicatorNames, $indicator->order . '. ' . $indicator->name);
            }
        }

        $inputs = self::getImportSpreadsheetInputs(
            $sheets,
            $questionnaire,
            $data['indicators_assigned_exact']['identifier'],
        );
        
        $indicators = self::getIndicatorsFromInputs($inputs);

        if (!$explicit)
        {
            $proccessedIndicators = self::getProcessedIndicators($questionnaire, $indicators);
                
            if (!empty($proccessedIndicators))
            {
                $conflictIndicatorNames = [];
                foreach ($proccessedIndicators as $indicator) {
                    array_push($conflictIndicatorNames, $indicator->order . '. ' . $indicator->name);
                }

                File::delete($excel);

                return response()->json(
                    [
                        'list' => $conflictIndicatorNames,
                        'message' => 'The following indicators have already been answered.<br>By clicking Continue, their values will be overwritten.'
                    ],
                    409
                );
            }
        }

        foreach ($indicators as $indicator)
        {
            $survey_indicator = SurveyIndicator::getSurveyIndicator($questionnaire, $indicator);

            self::updateSurveyIndicatorAnswers($survey_indicator, $inputs);

            SurveyIndicator::saveSurveyIndicator($survey_indicator);
        }

        Audit::setCustomAuditEvent(
            QuestionnaireCountry::find($questionnaire->id),
            ['event' => 'imported', 'audit' => ['file' => $originalName]]
        );

        if (!empty($skipIndicatorNames)) {
            return response()->json(
                [
                    'list' => $skipIndicatorNames,
                    'message' => 'The following indicators are no longer assigned to you and their values have been skipped:'
                ],
                409
            );
        }

        return response()->json('ok', 200);
    }

    public static function getIndicatorsFromInputs($inputs)
    {
        $indicators = [];
        
        foreach ($inputs as $input)
        {
            $indicator = Indicator::find($input['id']);

            if (!isset($indicators[$indicator->id])) {
                $indicators[$indicator->id] = $indicator;
            }
        }
        
        return $indicators;
    }

    public static function downloadExcel($user, $year, $indicators, $type, $filename_to_read, $filename_to_download)
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load(storage_path() . '/app/offline-survey/' . $year . '/' . $filename_to_read);

        if ($type == 'survey_template' &&
            $user->permissions->first()->role['id'] > 1)
        {
            $sheets = $spreadsheet->getAllSheets();

            foreach ($sheets as $sheet)
            {
                $title = $sheet->getTitle();

                if (!is_numeric($title)) {
                    continue;
                }

                $rows = $sheet->toArray();

                $identifier = (isset($rows[0][3])) ? $rows[0][3] : null;

                // Remove sheet not assigned to user
                if (is_null($identifier) ||
                    !in_array($identifier, $indicators))
                {
                    $spreadsheet->removeSheetByIndex(
                        $spreadsheet->getIndex(
                            $spreadsheet->getSheetByName($title)
                        )
                    );
                }
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save(storage_path() . '/app/' . $filename_to_download);

        return true;
    }
}
