<?php

namespace App\Http\Controllers;

use App\Notifications\NotifyUser;
use App\HelperFunctions\GeneralHelper;
use App\HelperFunctions\QuestionnaireCountryHelper;
use App\HelperFunctions\IndexDataCollectionHelper;
use App\HelperFunctions\TaskHelper;
use App\HelperFunctions\UserPermissions;
use App\Jobs\IndicatorValuesCalculation;
use App\Models\Audit;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use App\Models\Indicator;
use App\Models\IndicatorRequestedChange;
use App\Models\IndicatorValue;
use App\Models\SurveyIndicator;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class QuestionnaireCountryController extends Controller
{
    const ERROR_NOT_AUTHORIZED = 'Indicator cannot be updated as you are not authorized for this action!';
    const ERROR_NOT_ALLOWED = 'Indicator cannot be updated as the requested action is not allowed!';
    const INDICATORVALUESCALCULATIONTASK = 'IndicatorValuesCalculation';

    public function viewUserQuestionnaires()
    {
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountrySummaryData();

        return view('questionnaire.management', [
            'questionnaires' => $data['questionnaires'],
            'questionnaires_assigned' => $data['questionnaires_assigned']
        ]);
    }

    public function viewQuestionnaire(Request $request, QuestionnaireCountry $questionnaire)
    {
        $action = $request['action'];
        $requested_indicator = (isset($request['requested_indicator'])) ? $request['requested_indicator'] : null;
        $requested_action = (isset($request['requested_action'])) ? $request['requested_action'] : null;

        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        
        if ($action == 'view' &&
            (($data['indicators_submitted'] && Auth::user()->isOperator()) ||
             ($questionnaire->submitted_by && Auth::user()->isPoC()) ||
             ($questionnaire->approved_by && Auth::user()->isAdmin())))
        {
            Audit::setCustomAuditEvent(
                QuestionnaireCountry::find($questionnaire->id),
                ['event' => 'viewed', 'audit' => ['status' => 'Submitted']]
            );
        }
        elseif ($action == 'export') {
            Audit::setCustomAuditEvent(
                QuestionnaireCountry::find($questionnaire->id),
                [
                    'event' => 'exported', 'audit' =>
                    ['status' => ($questionnaire->submitted_by ? 'Submitted' : 'Pending')]
                ]
            );
        }

        $questionnaire->questionnaire->deadline = GeneralHelper::dateFormat($questionnaire->questionnaire->deadline, 'd-m-Y');

        $last_questionnaire_country = null;
        $last_published_questionnaire = Questionnaire::getLastPublishedQuestionnaire();
        if (!is_null($last_published_questionnaire)) {
            $last_questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $last_published_questionnaire->id)->where('country_id', $questionnaire->country_id)->first();
        }
        
        return view('questionnaire.view', [
            'action' => $action,
            'questionnaire' => $questionnaire,
            'last_questionnaire_country' => $last_questionnaire_country,
            'questionnaire_started' => $data['questionnaire_started'],
            'requested_indicator' => $requested_indicator,
            'requested_action' => $requested_action,
            'indicators_assigned' => $data['indicators_assigned'],
            'indicators_assigned_exact' => $data['indicators_assigned_exact'],
            'indicators_submitted' => $data['indicators_submitted']
        ]);
    }

    public function previewQuestionnaire(Request $request, Indicator $indicator = null)
    {
        $inputs = $request->all();
        $with_answers = filter_var($inputs['with_answers'], FILTER_VALIDATE_BOOLEAN);
        $questionnaire_country = null;

        if ($with_answers)
        {
            $questionnaire_country = QuestionnaireCountry::find($inputs['questionnaire_country_id']);

            $indicators = Indicator::getIndicatorsWithSurveyAndAnswers($questionnaire_country);
        }
        else {
            $indicators = Indicator::getIndicatorsWithSurvey($_COOKIE['index-year']);
        }
        
        if (!is_null($indicator)) {
            $indicators = [
                $indicator->id => $indicators[$indicator->id]
            ];
        }
        
        return view('questionnaire.preview', [
            'indicators' => $indicators,
            'with_answers' => $with_answers,
            'questionnaire_country' => $questionnaire_country
        ]);
    }

    public function validateQuestionnaireOffline(QuestionnaireCountry $questionnaire)
    {
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);

        if (empty($data['indicators_assigned_exact'])) {
            return response()->json(['warning' => 'You haven\'t been assigned any indicators!'], 403);
        }

        return response()->json('ok', 200);
    }

    public function uploadQuestionnaire(Request $request, QuestionnaireCountry $questionnaire)
    {
        $file = $request->file('file');
        $request['extension'] = strtolower($file->getClientOriginalExtension());
        $explicit = $request->has('explicit_flag') ? $request->get('explicit_flag') : false;

        $validator = QuestionnaireCountryHelper::validateQuestionnaireTemplateUpload($request);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        $current_year = date('Y');

        $path = storage_path() . '/app/offline-survey/' . $current_year;

        File::ensureDirectoryExists($path);

        $originalName = htmlspecialchars($file->getClientOriginalName());
        $filename = time() . '_' . $questionnaire->country->code . '_' . $originalName;

        $file->move($path, $filename);

        $excel = $path . '/' . $filename;

        try {
            return QuestionnaireCountryHelper::questionnaireUploadFile(
                $excel,
                $questionnaire,
                $originalName,
                $explicit
            );
        }
        catch (\Exception $e)
        {
            Log::debug($e->getMessage());

            File::delete($excel);

            return response()->json(['error' => 'File could not be parsed correctly!'], 400);
        }
    }

    public function viewQuestionnaireCountriesDashboard(Questionnaire $questionnaire)
    {
        $published_questionnaires = Questionnaire::getPublishedQuestionnaires();

        return view('questionnaire.admin-dashboard', ['published_questionnaires' => $published_questionnaires, 'questionnaire' => $questionnaire]);
    }

    public function listQuestionnaireCountriesDashboard(Questionnaire $questionnaire)
    {
        $countries = QuestionnaireCountryHelper::getQuestionnaireCountries($questionnaire);

        $data = [];
        foreach ($countries as $country)
        {
            $country_attributes = $country->getAttributes();
            
            $questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $questionnaire->id)->where('country_id', $country->country_id)->first();

            if (!is_null($questionnaire_country))
            {
                QuestionnaireCountryHelper::getQuestionnaireCountryData($questionnaire_country);

                $questionnaire_country_attributes = $questionnaire_country->getAttributes();

                $country_attributes = array_merge($country_attributes, $questionnaire_country_attributes);
            }

            array_push($data, $country_attributes);
        }
        
        return response()->json(['data' => $data], 200);
    }

    public function viewQuestionnaireCountryDashboard(QuestionnaireCountry $questionnaire)
    {
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);

        $questionnaire->questionnaire->deadline = GeneralHelper::dateFormat(
            $questionnaire->questionnaire->deadline,
            'd-m-Y'
        );
        
        $view_data = [
            'questionnaire' => $questionnaire,
            'indicators_percentage' => $data['indicators_percentage'],
            'pending_requested_changes' => IndicatorRequestedChange::getQuestionnaireCountryRequestedChanges($questionnaire, [1]),
            'indicators_approved' => $data['indicators_approved']
        ];

        $currenturl = url()->current();

        if (preg_match('/admin/', $currenturl)) {
            return view('questionnaire.admin-dashboard-management', $view_data);
        }

        $view_data['indicators_assigned'] = $data['indicators_assigned'];

        return view('questionnaire.dashboard-management', $view_data);
    }

    public function listQuestionnaireCountryDashboard(QuestionnaireCountry $questionnaire)
    {
        $indicators = QuestionnaireCountryHelper::getQuestionnaireCountryIndicators($questionnaire);

        return response()->json(['data' => $indicators], 200);
    }

    public function viewQuestionnaireCountrySummaryData(QuestionnaireCountry $questionnaire)
    {
        QuestionnaireCountryHelper::getQuestionnaireCountryData($questionnaire);

        $survey_indicators = SurveyIndicator::getSurveyIndicators($questionnaire);

        $requested_changes = IndicatorRequestedChange::getQuestionnaireCountryRequestedChanges($questionnaire, [], false);
        $data_not_available = QuestionnaireCountryHelper::getQuestionnaireCountryDataNotAvailable($survey_indicators);
        $references = QuestionnaireCountryHelper::getQuestionnaireCountryDataReferences($survey_indicators);
        $comments = QuestionnaireCountryHelper::getQuestionnaireCountryDataComments($survey_indicators);
        
        return view('questionnaire.dashboard-summary-data', [
            'questionnaire' => $questionnaire,
            'requested_changes' => $requested_changes,
            'data_not_available' => $data_not_available,
            'references' => $references,
            'comments' => $comments
        ]);
    }

    public function viewQuestionnaireCountryIndicatorValues(Questionnaire $questionnaire)
    {
        $inputs = [
            'questionnaire' => $questionnaire,
            'index' => $questionnaire->configuration,
            'category' => 'survey'
        ];
        $indicators = [];
        $countries = [];

        $task = TaskHelper::getTask([
            'type' => self::INDICATORVALUESCALCULATIONTASK,
            'index_configuration_id' => $questionnaire->configuration->id
        ]);

        $published_questionnaires = Questionnaire::getPublishedQuestionnaires();

        $data = IndexDataCollectionHelper::getIndicatorValueData($inputs);

        $filter_data = IndexDataCollectionHelper::getFilterData($data);
        if (!empty($filter_data)) {
            list($indicators, $countries) = $filter_data;
        }

        return view('questionnaire.admin-dashboard-indicator-values', [
            'published_questionnaires' => $published_questionnaires,
            'questionnaire' => $questionnaire,
            'task' => $task,
            'indicators' => collect($indicators)->sort(),
            'countries' => collect($countries)->sort(),
            'table_data' => $data
        ]);
    }

    public function listQuestionnaireCountryIndicatorValues(Request $request, Questionnaire $questionnaire)
    {
        $inputs = $request->all();
        $inputs['questionnaire'] = $questionnaire;
        $inputs['index'] = $questionnaire->configuration;
        $inputs['category'] = 'survey';

        $data = IndexDataCollectionHelper::getIndicatorValueData($inputs);

        return response()->json(['data' => $data], 200);
    }

    public function calculateQuestionnaireCountryIndicatorValues(Questionnaire $questionnaire)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Calculation cannot be performed as you are not authorized for this action!'], 403);
        }

        $task = TaskHelper::getTask([
            'type' => self::INDICATORVALUESCALCULATIONTASK,
            'index_configuration_id' => $questionnaire->configuration->id
        ]);

        $latest_questionnaire_data = Questionnaire::getLatestPublishedQuestionnaire();
        if ($questionnaire->id != $latest_questionnaire_data->id ||
            ($task && $task->status_id == 1))
        {
            return response()->json(['error' => self::ERROR_NOT_ALLOWED], 405);
        }

        IndicatorValue::disableAuditing();
        IndicatorValuesCalculation::dispatch($questionnaire->configuration, Auth::user(), $questionnaire);
        IndicatorValue::enableAuditing();

        Audit::setCustomAuditEvent(
            Questionnaire::find($questionnaire->id),
            ['event' => 'updated', 'audit' => ['status' => 'Calculated Indicator Values']]
        );

        return response()->json('ok', 200);
    }

    public function getQuestionnaireCountryIndicatorInfo(Request $request, Indicator $indicator)
    {
        $inputs = $request->all();
        
        $questionnaire = QuestionnaireCountry::find($inputs['questionnaire_country_id']);
        $survey_indicator = SurveyIndicator::getSurveyIndicator($questionnaire, $indicator);

        return view('ajax.questionnaire-indicator-info', ['survey_indicator' => $survey_indicator]);
    }

    public function indicatorManagement($inputs, $indicators)
    {
        $questionnaire = QuestionnaireCountry::find($inputs['questionnaire_country_id']);
        $questionnaire->questionnaire->deadline = GeneralHelper::dateFormat(
            $questionnaire->questionnaire->deadline,
            'd-m-Y'
        );
        $processedIndicators = QuestionnaireCountryHelper::getProcessedIndicators(
            $questionnaire,
            $indicators
        );
        if (count($indicators) == 1)
        {
            $indicator = &$indicators[0];
            $survey_indicator = SurveyIndicator::getSurveyIndicator($questionnaire, $indicator);
            
            $indicator->deadline = GeneralHelper::dateFormat($survey_indicator->deadline, 'd-m-Y');
            $indicator->assignee = $survey_indicator->assignee;
        }

        $country_code = UserPermissions::getUserCountries('code');
        [$users] = User::getListOfUsersByCountryAndRole([2, 3, 5], $country_code);
        
        return view(
            'ajax.questionnaire-indicator-management',
            [
                'questionnaire' => $questionnaire,
                'indicators' => $indicators,
                'users' => $users,
                'processedIndicators' => $processedIndicators
            ]
        );
    }

    public function getQuestionnaireCountryIndicator(Request $request, Indicator $indicator)
    {
        $inputs = $request->all();

        return $this->indicatorManagement($inputs, [$indicator]);
    }

    public function getQuestionnaireCountryIndicators(Request $request)
    {
        $inputs = $request->all();

        $indicatorIds = array_filter(explode(',', $inputs['indicators']));
        $indicators = Indicator::whereIn('id', $indicatorIds)->get();

        return $this->indicatorManagement($inputs, $indicators);
    }

    public function updateQuestionnaireCountryIndicator(Request $request, Indicator $indicator)
    {
        $inputs = $request->all();
        $inputs['users'] = [];
        $country_code = UserPermissions::getUserCountries('code');
        [$users] = User::getListOfUsersByCountryAndRole([2, 3, 5], $country_code);
        foreach ($users as $user) {
            array_push($inputs['users'], $user->id);
        }
        
        $questionnaire = QuestionnaireCountry::find($inputs['questionnaire_country_id']);

        if (!QuestionnaireCountryHelper::canUpdateQuestionnaireCountryIndicatorData($inputs)) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        if ($inputs['action'] == 'edit')
        {
            $validator = QuestionnaireCountryHelper::validateIndicatorInputs($questionnaire, $inputs);
            if ($validator->fails()) {
                return response()->json($validator->messages(), 400);
            }
        }

        $resp = QuestionnaireCountryHelper::updateSurveyIndicatorsData($questionnaire, collect([$indicator]), $inputs);
        if ($resp['type'] == 'warning' ||
            $resp['type'] == 'error')
        {
            return response()->json([$resp['type'] => $resp['msg']], $resp['status']);
        }

        if ($inputs['action'] == 'edit') {
            QuestionnaireCountryHelper::notifyUserForIndicators($questionnaire, $inputs, $resp);
        }
        
        if ($inputs['action'] == 'final_approve')
        {
            $survey_indicators = SurveyIndicator::getSurveyIndicators($questionnaire);

            $indicators_data = QuestionnaireCountryHelper::getSurveyIndicatorsData($questionnaire, $survey_indicators);

            return response()->json(['approved' => $indicators_data['approved']], 200);
        }

        return response()->json('ok', 200);
    }

    public function updateQuestionnaireCountryIndicators(Request $request)
    {
        $inputs = $request->all();
        $inputs['users'] = [];
        $country_code = UserPermissions::getUserCountries('code');
        [$users] = User::getListOfUsersByCountryAndRole([2, 3, 5], $country_code);
        foreach ($users as $user) {
            array_push($inputs['users'], $user->id);
        }

        if (isset($inputs['datatable-selected']))
        {
            $indicatorIds = array_filter(explode(',', $inputs['datatable-selected']));
            $indicators = Indicator::whereIn('id', $indicatorIds)->get();
        }
        else {
            $indicators = Indicator::all();
        }

        $questionnaire = QuestionnaireCountry::find($inputs['questionnaire_country_id']);

        if (!QuestionnaireCountryHelper::canUpdateQuestionnaireCountryIndicatorData($inputs)) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        if ($inputs['action'] == 'edit')
        {
            $validator = QuestionnaireCountryHelper::validateIndicatorInputs($questionnaire, $inputs);
            if ($validator->fails()) {
                return response()->json($validator->messages(), 400);
            }
        }

        $resp = QuestionnaireCountryHelper::updateSurveyIndicatorsData($questionnaire, $indicators, $inputs);
        if ($resp['type'] == 'error') {
            return response()->json([$resp['type'] => $resp['msg']], $resp['status']);
        }

        if ($inputs['action'] == 'edit') {
            QuestionnaireCountryHelper::notifyUserForIndicators($questionnaire, $inputs, $resp);
        }

        if ($inputs['action'] == 'final_approve')
        {
            $survey_indicators = SurveyIndicator::getSurveyIndicators($questionnaire);

            $indicators_data = QuestionnaireCountryHelper::getSurveyIndicatorsData($questionnaire, $survey_indicators);

            return response()->json(['approved' => $indicators_data['approved']], 200);
        }

        return response()->json('ok', 200);
    }

    public function validateQuestionnaireIndicator(Request $request, QuestionnaireCountry $questionnaire)
    {
        $inputs = $request->all();
        $indicator_answers = json_decode(htmlspecialchars_decode($inputs['indicator_answers']), true);

        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);

        $messages = QuestionnaireCountryHelper::validateAnswers($indicator_answers, $data);
        if (!empty($messages)) {
            return response()->json(['errors' => $messages], 400);
        }

        return response()->json('ok', 200);
    }

    public function loadSurveyIndicatorData(Request $request, QuestionnaireCountry $questionnaire)
    {
        $inputs = $request->all();

        $indicator = Indicator::find($inputs['active_indicator']);
        $survey_indicator = SurveyIndicator::getSurveyIndicator($questionnaire, $indicator);

        if (!QuestionnaireCountryHelper::canLoadResetSurveyIndicatorData($survey_indicator)) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $survey_indicator->answers_loaded = true;

        SurveyIndicator::saveSurveyIndicator($survey_indicator);

        return response()->json('ok', 200);
    }

    public function resetSurveyIndicatorData(Request $request, QuestionnaireCountry $questionnaire)
    {
        $inputs = $request->all();

        $indicator = Indicator::find($inputs['active_indicator']);
        $survey_indicator = SurveyIndicator::getSurveyIndicator($questionnaire, $indicator);

        if (!QuestionnaireCountryHelper::canLoadResetSurveyIndicatorData($survey_indicator)) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $survey_indicator->answers_loaded = false;

        SurveyIndicator::saveSurveyIndicator($survey_indicator);
        
        return response()->json('ok', 200);
    }

    public function saveQuestionnaire(Request $request, QuestionnaireCountry $questionnaire)
    {
        $inputs = $request->all();
        $indicators_list = $inputs['indicators_list'];
        $active_indicator = Indicator::find($inputs['active_indicator']);
        $questionnaire_answers = $inputs['questionnaire_answers'] = json_decode(htmlspecialchars_decode($inputs['questionnaire_answers']), true);
        $indicator_answers = $inputs['indicator_answers'] = json_decode(htmlspecialchars_decode($inputs['indicator_answers']), true);
        $indicators = [];
        
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);

        if (empty($data['indicators_assigned'])) {
            return response()->json(
                [
                    'warning' => 'You haven\'t been assigned any indicators!',
                    'indicators_assigned' => count($data['indicators_assigned'])
                ],
                403
            );
        }

        if (!in_array($active_indicator->id, $data['indicators_assigned']['id'])) {
            return response()->json(
                [
                    'warning' => 'You are no longer assigned this indicator. Please start the survey again!',
                    'indicators_assigned' => count($data['indicators_assigned'])
                ],
                403
            );
        }

        if ($inputs['action'] == 'save') {
            $indicators = collect([$active_indicator]);
        }
        elseif ($inputs['action'] == 'submit')
        {
            $messages = QuestionnaireCountryHelper::validateAnswers($questionnaire_answers, $data);
            if (!empty($messages)) {
                return response()->json(['error' => 'Survey answers are invalid. Please check the answers again!'], 400);
            }

            sort($indicators_list);
            sort($data['indicators_assigned']['id']);

            if (array_map('intval', $indicators_list) !== $data['indicators_assigned']['id'] &&
                $questionnaire->default_assignee != Auth::user()->id)
            {
                return response()->json(
                    [
                        'warning' => 'You are no longer assigned these indicators. Please start the survey again!',
                        'indicators_assigned' => count($data['indicators_assigned'])
                    ],
                    403
                );
            }

            $indicators = Indicator::getIndicatorsWithIds($indicators_list);
        }

        $resp = QuestionnaireCountryHelper::updateSurveyIndicatorsData($questionnaire, $indicators, $inputs);
        if ($resp['type'] == 'warning' ||
            $resp['type'] == 'error')
        {
            return response()->json([$resp['type'] => $resp['msg']], $resp['status']);
        }

        if ($inputs['action'] == 'submit')
        {
            // Submitted by default assignee
            if ($questionnaire->default_assignee == Auth::user()->id)
            {
                $questionnaire->submitted_by = Auth::user()->id;
                $questionnaire->submitted_at = Carbon::now();

                QuestionnaireCountry::saveQuestionnaireCountry($questionnaire);

                $data = [];
                $data['role_id'] = 1;
                $enisaAdmins = User::getUsersByCountryAndRole($data);

                foreach ($enisaAdmins as $enisaAdmin) {
                    $enisaAdmin->user->notify(new NotifyUser([
                        'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
                        'subject' => 'MS Survey - Submitted',
                        'markdown' => 'questionnaire-submitted',
                        'maildata' => [
                            'questionnaire' => $questionnaire->questionnaire->title,
                            'country' => $questionnaire->country->name,
                            'author' => Auth::user()->name
                        ]
                    ]));
                }
            }
            // Submitted by assignee
            else
            {
                $survey_indicators = SurveyIndicator::getSurveyIndicators($questionnaire);

                $indicators_data = QuestionnaireCountryHelper::getSurveyIndicatorsData($questionnaire, $survey_indicators);

                if ($indicators_data['completed'])
                {
                    $questionnaire->completed = true;

                    QuestionnaireCountry::saveQuestionnaireCountry($questionnaire);
                }

                $user = User::find($questionnaire->default_assignee);
                $user->notify(new NotifyUser([
                    'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
                    'subject' => 'MS Survey - Submitted',
                    'markdown' => 'questionnaire-submitted',
                    'maildata' => [
                        'questionnaire' => $questionnaire->questionnaire->title,
                        'country' => $questionnaire->country->name,
                        'author' => Auth::user()->name
                    ]
                ]));
            }
            
            IndicatorRequestedChange::answerQuestionnaireCountryRequestedChanges($questionnaire);

            Audit::setCustomAuditEvent(
                QuestionnaireCountry::find($questionnaire->id),
                ['event' => 'updated', 'audit' => ['status' => 'Submitted', 'data' => $questionnaire_answers]]
            );
        }

        return response()->json($resp['data'], 200);
    }

    public function requestChangesQuestionnaireCountryIndicator(Request $request, Indicator $indicator)
    {
        $inputs = $request->all();

        if (!QuestionnaireCountryHelper::canRequestChangesQuestionnaireCountryIndicator()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $validator = QuestionnaireCountryHelper::validateRequestChangesInputs($inputs);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        $questionnaire = QuestionnaireCountry::find($inputs['questionnaire_country_id']);

        $resp = QuestionnaireCountryHelper::requestChangesQuestionnaireCountryIndicator($questionnaire, $indicator, $inputs);
        if ($resp['type'] == 'warning' ||
            $resp['type'] == 'error')
        {
            return response()->json([$resp['type'] => $resp['msg']], $resp['status']);
        }

        return response()->json('ok', 200);
    }

    public function discardRequestedChangesQuestionnaireCountryIndicator(Request $request, Indicator $indicator)
    {
        $inputs = $request->all();

        if (!QuestionnaireCountryHelper::canDiscardRequestedChangesQuestionnaireCountryIndicator()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $questionnaire = QuestionnaireCountry::find($inputs['questionnaire_country_id']);

        $resp = QuestionnaireCountryHelper::discardRequestedChangesQuestionnaireCountryIndicator($questionnaire, $indicator);
        if ($resp['type'] == 'error') {
            return response()->json([$resp['type'] => $resp['msg']], $resp['status']);
        }
        
        return response()->json('ok', 200);
    }

    public function submitQuestionnaireCountryRequestedChanges(Request $request)
    {
        $inputs = $request->all();

        if (!QuestionnaireCountryHelper::canSubmitQuestionnaireCountryRequestedChanges($inputs)) {
            return response()->json(['error' => 'Requested changes cannot be submitted as you are not authorized for this action!'], 403);
        }

        $questionnaire = QuestionnaireCountry::find($inputs['questionnaire_country_id']);

        $pending_requested_changes = QuestionnaireCountryHelper::submitQuestionnaireCountryRequestedChanges($questionnaire);

        if (Auth::user()->isAdmin() ||
            Auth::user()->isPoC())
        {
            if (Auth::user()->isAdmin())
            {
                $questionnaire->submitted_by = null;
                $questionnaire->submitted_at = null;
                $questionnaire->requested_changes_submitted_at = Carbon::now();
            }
            elseif (Auth::user()->isPoC()) {
                $questionnaire->completed = false;
            }

            QuestionnaireCountry::saveQuestionnaireCountry($questionnaire);
        }

        $notify_data = IndicatorRequestedChange::getQuestionnaireCountryRequestedChangesNotifyData($questionnaire, $pending_requested_changes);
        
        foreach ($notify_data as $assignee => $indicators)
        {
            $user = User::find($assignee);
            $user->notify(new NotifyUser([
                'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
                'subject' => 'EU Cybersecurity Index Survey - Request for revision',
                'markdown' => 'questionnaire-indicators-requested-changes',
                'maildata' => [
                    'url' => env('APP_URL') . '/questionnaire/management',
                    'questionnaire' => $questionnaire->questionnaire->title,
                    'indicators' => $indicators,
                    'deadline' => GeneralHelper::dateFormat($pending_requested_changes->sortBy('deadline')->value('deadline'), 'd-m-Y'),
                    'author' => (Auth::user()->isAdmin()) ? config('constants.USER_GROUP') : Auth::user()->name
                ]
            ]));
        }

        Audit::setCustomAuditEvent(
            QuestionnaireCountry::find($questionnaire->id),
            ['event' => 'updated', 'audit' => ['status' => 'Submitted Requested Changes']]
        );

        return response()->json('ok', 200);
    }

    public function finaliseSurvey(Request $request)
    {
        $inputs = $request->all();

        if (!QuestionnaireCountryHelper::canFinaliseSurvey()) {
            return response()->json(['error' => 'Survey cannot be finalised as you are not authorized for this action!'], 403);
        }

        $questionnaire = QuestionnaireCountry::find($inputs['questionnaire_country_id']);
        $questionnaire->approved_by = Auth::user()->id;

        QuestionnaireCountry::saveQuestionnaireCountry($questionnaire);

        $user = User::find($questionnaire->default_assignee);
        $user->notify(new NotifyUser([
            'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
            'subject' => 'MS Survey - Finalised',
            'markdown' => 'questionnaire-finalised',
            'maildata' => [
                'questionnaire' => $questionnaire->questionnaire->title,
                'country' => $questionnaire->country->name,
                'name' => $user->name
            ]
        ]));

        Audit::setCustomAuditEvent(
            QuestionnaireCountry::find($questionnaire->id),
            ['event' => 'updated', 'audit' => ['status' => 'Finalised Survey']]
        );

        return response()->json('ok', 200);
    }
}
