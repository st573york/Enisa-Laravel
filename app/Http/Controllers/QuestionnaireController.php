<?php

namespace App\Http\Controllers;

use App\HelperFunctions\QuestionnaireHelper;
use App\Models\Audit;
use App\Models\Questionnaire;
use App\Models\IndexConfiguration;
use App\Models\QuestionnaireCountry;
use App\Models\QuestionnaireIndicator;
use App\Models\QuestionnaireUser;
use App\Models\User;
use Illuminate\Http\Request;

class QuestionnaireController extends Controller
{
    public function viewQuestionnaires()
    {
        return view('questionnaire.admin-management');
    }

    public function listQuestionnaires()
    {
        $questionnaires = QuestionnaireHelper::getQuestionnaires();

        foreach ($questionnaires as $questionnaire)
        {
            $user = User::withTrashed()->where('name', $questionnaire->created_by)->first();
            $questionnaire->created_by .= ($user->trashed() ? ' ' . config('constants.USER_INACTIVE') : '');
        }

        return response()->json(['data' => $questionnaires], 200);
    }

    public function createOrShowQuestionnaire($action = 'create', $data = null)
    {
        $indexes = IndexConfiguration::getIndexConfigurations();
        
        return view('ajax.questionnaire-management', ['action' => $action, 'data' => $data, 'indexes' => $indexes]);
    }

    public function storeQuestionnaire(Request $request)
    {
        $inputs = $request->all();

        $validator = QuestionnaireHelper::validateInputsForCreate($inputs);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages(), 'type' => 'pageModalForm'], 400);
        }

        $index_data = IndexConfiguration::getIndexConfiguration($inputs['index_configuration_id']);
        if (!QuestionnaireHelper::areIndicatorsValidated($index_data->year)) {
            return response()->json(['error' => 'Survey for cannot be created. Please validate all indicators first!', 'type' => 'pageAlert'], 405);
        }

        QuestionnaireIndicator::disableAuditing();
        QuestionnaireHelper::storeQuestionnaire($inputs);
        QuestionnaireIndicator::enableAuditing();

        return response()->json('ok', 200);
    }

    public function showQuestionnaire(Questionnaire $questionnaire)
    {
        $data = QuestionnaireHelper::getQuestionnaire($questionnaire->id);

        return $this->createOrShowQuestionnaire('show', $data);
    }

    public function updateQuestionnaire(Request $request, Questionnaire $questionnaire)
    {
        $inputs = $request->all();
        
        $validator = QuestionnaireHelper::validateInputsForEdit($inputs);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages(), 'type' => 'pageModalForm'], 400);
        }

        if (!QuestionnaireHelper::areIndicatorsValidated($questionnaire->year)) {
            return response()->json(['error' => 'Survey for cannot be updated. Please validate all indicators first!', 'type' => 'pageAlert'], 405);
        }

        QuestionnaireHelper::updateQuestionnaire($questionnaire->id, $inputs);

        return response()->json('ok', 200);
    }

    public function showOrPublishQuestionnaire(Questionnaire $questionnaire, $action = 'publish')
    {
        $data = QuestionnaireHelper::getQuestionnaire($questionnaire->id);

        return view('ajax.questionnaire-management', ['action' => $action, 'data' => $data]);
    }

    public function listUsers(Questionnaire $questionnaire)
    {
        $questionnaire_users = QuestionnaireHelper::getQuestionnaireUsers($questionnaire);

        return response()->json(['data' => $questionnaire_users], 200);
    }

    public function createUserQuestionnaire(Request $request, Questionnaire $questionnaire)
    {
        $inputs = $request->all();

        $users = [];
        if (isset($inputs['datatable-selected']) &&
            isset($inputs['datatable-all']))
        {
            if ($inputs['notify_users'] == 'radio-specific') {
                $users = array_filter(explode(',', $inputs['datatable-selected']));
            }
            elseif ($inputs['notify_users'] == 'radio-all') {
                $users = array_filter(explode(',', $inputs['datatable-all']));
            }
        }

        if (empty($users)) {
            return response()->json(['error' => 'You haven\'t selected any users!', 'type' => 'pageModalAlert'], 400);
        }

        if (!QuestionnaireHelper::areIndicatorsValidated($questionnaire->year)) {
            return response()->json(['error' => 'Survey for cannot be published. Please validate all indicators first!', 'type' => 'pageAlert'], 405);
        }

        Questionnaire::disableAuditing();
        QuestionnaireCountry::disableAuditing();
        QuestionnaireUser::disableAuditing();
        QuestionnaireHelper::publishQuestionnaire($questionnaire);
        $questionnaire_users = QuestionnaireHelper::createQuestionnaireUsers($questionnaire, $users);
        QuestionnaireHelper::createQuestionnaireTemplate($questionnaire->year);
        Questionnaire::enableAuditing();
        QuestionnaireCountry::enableAuditing();
        QuestionnaireUser::enableAuditing();

        Audit::setCustomAuditEvent(
            Questionnaire::find($questionnaire->id),
            ['event' => 'updated', 'audit' => ['status' => 'Published', 'users' => implode(', ', $questionnaire_users)]]
        );

        return response()->json('ok', 200);
    }

    public function sendReminderForPublishedQuestionnaire(Questionnaire $questionnaire)
    {
        QuestionnaireHelper::sendReminderForPublishedQuestionnaire($questionnaire);

        Audit::setCustomAuditEvent(
            Questionnaire::find($questionnaire->id),
            ['event' => 'updated', 'audit' => ['status' => 'Sent Notifications']]
        );

        return response()->json('ok', 200);
    }

    public function deleteQuestionnaire(Questionnaire $questionnaire)
    {
        if (!QuestionnaireHelper::deleteQuestionnaire($questionnaire->id)) {
            return response()->json(['error' => 'Survey cannot be deleted as it is published!', 'type' => 'pageAlert'], 405);
        }

        return response()->json('ok', 200);
    }
}
