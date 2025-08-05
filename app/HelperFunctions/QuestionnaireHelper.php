<?php

namespace App\HelperFunctions;

use App\Notifications\NotifyUser;
use App\HelperFunctions\GeneralHelper;
use App\Models\Country;
use App\Models\Questionnaire;
use App\Models\QuestionnaireUser;
use App\Models\User;
use App\Models\IndexConfiguration;
use App\Models\Indicator;
use App\Models\IndicatorAccordion;
use App\Models\Permission;
use App\Models\QuestionnaireCountry;
use App\Models\QuestionnaireIndicator;
use App\Models\SurveyIndicator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class QuestionnaireHelper
{
    public static function validateInputsForCreate($inputs)
    {
        return validator(
            $inputs,
            [
                'index_configuration_id' => [Rule::when(
                    empty($inputs['index']),
                    'required'
                )],
                'title' => ['required', Rule::unique('questionnaires')],
                'deadline' => 'required'
            ],
            [
                'index_configuration_id.required' => 'The index field is required.'
            ]
        );
    }

    public static function validateInputsForEdit($inputs)
    {
        return validator(
            $inputs,
            [
                'index_configuration_id' => [Rule::when(
                    empty($inputs['index']),
                    'required'
                )],
                'title' => ['required', Rule::unique('questionnaires')->ignore($inputs['id'])],
                'deadline' => 'required'
            ],
            [
                'index_configuration_id.required' => 'The index field is required.'
            ]
        );
    }

    public static function areIndicatorsValidated($year)
    {
        $indicators_not_validated = Indicator::where('validated', false)->where('category', 'survey')->where('year', $year)->get();

        if ($indicators_not_validated->count()) {
            return false;
        }

        return true;
    }

    public static function getQuestionnaireData($data)
    {
        $index_data = IndexConfiguration::getIndexConfiguration($data['index_configuration_id']);

        return [
            'title' => $data['title'],
            'description' => urldecode($data['description']),
            'year' => $index_data->year,
            'deadline' => GeneralHelper::dateFormat($data['deadline']),
            'index_configuration_id' => $data['index_configuration_id'],
            'user_id' => Auth::user()->id
        ];
    }

    public static function getQuestionnaires()
    {
        return Questionnaire::select(
            'questionnaires.id',
            'questionnaires.title',
            'questionnaires.year',
            'questionnaires.deadline',
            'questionnaires.published',
            'users.name AS created_by',
            DB::raw('COUNT(questionnaire_countries.id) AS not_submitted')
        )
            ->leftJoin('users', 'users.id', '=', 'questionnaires.user_id')
            ->leftJoin('questionnaire_countries', function ($join) {
                $join->on('questionnaire_countries.questionnaire_id', '=', 'questionnaires.id')->whereNull('questionnaire_countries.submitted_by');
            })
            ->groupBy('id')
            ->get();
    }

    public static function getQuestionnaire($id)
    {
        return Questionnaire::select('*', DB::raw('DATE_FORMAT(deadline, "%d-%m-%Y") AS deadline'))->find($id);
    }

    public static function getQuestionnaireUsers($questionnaire)
    {
        return Permission::select(
            'permissions.user_id AS id',
            'users.name AS name',
            'users.email AS email',
            'roles.name AS role',
            'countries.name AS country',
            DB::raw('
                (
                    CASE
                        WHEN questionnaire_countries.id IS NOT NULL THEN 1
                        ELSE 0
                    END
                ) AS notified')
        )
            ->leftJoin('users', 'users.id', '=', 'permissions.user_id')
            ->leftJoin('roles', 'roles.id', '=', 'permissions.role_id')
            ->leftJoin('countries', 'countries.id', '=', 'permissions.country_id')
            ->leftJoin('questionnaire_users', function ($join) use ($questionnaire) {
                $join->on('questionnaire_users.user_id', '=', 'permissions.user_id')->whereIn('questionnaire_users.questionnaire_country_id', QuestionnaireCountry::where('questionnaire_id', $questionnaire->id)->pluck('id')->toArray());
            })
            ->leftJoin('questionnaire_countries', 'questionnaire_countries.id', '=', 'questionnaire_users.questionnaire_country_id')
            ->where('permissions.role_id', 5)
            ->where('users.blocked', 0)
            ->groupBy('id', 'name', 'role', 'country', 'notified')
            ->get();
    }

    public static function storeQuestionnaire($data)
    {
        $questionnaire = Questionnaire::create(self::getQuestionnaireData($data));
        $configuration = $questionnaire->configuration;

        $indicatorIds = [];
        foreach ($configuration->json_data['contents'] as $area)
        {
            if (isset($area['area']['subareas']))
            {
                foreach ($area['area']['subareas'] as $subarea)
                {
                    if (isset($subarea['indicators']))
                    {
                        foreach ($subarea['indicators'] as $indicator) {
                            array_push($indicatorIds, $indicator['id']);
                        }
                    }
                }
            }
        }

        $indicators = Indicator::whereIn('id', $indicatorIds)->where('category', 'survey')->orderBy('order')->get();
        foreach ($indicators as $indicator) {
            QuestionnaireIndicator::create([
                'indicator_id' => $indicator['id'],
                'questionnaire_id' => $questionnaire->id
            ]);
        }

        return $questionnaire;
    }

    public static function updateQuestionnaire($id, $data)
    {
        Questionnaire::find($id)->update(self::getQuestionnaireData($data));
    }

    public static function createQuestionnaireUsers(Questionnaire $questionnaire, $users)
    {
        $questionnaire_users = [];
        
        foreach ($users as $id)
        {
            $user = User::find($id);
            $availableCountries = UserPermissions::getUserCountries('entity', $user);

            foreach ($availableCountries as $country)
            {
                $countryPrimaryPoC = User::getCountryPrimaryPoC($country);
                $assignee = ($countryPrimaryPoC) ? User::find($countryPrimaryPoC->user_id) : $user;
                
                $existingCountryQuestionnaire = QuestionnaireCountry::where([
                    ['questionnaire_id', $questionnaire->id],
                    ['country_id', $country->id]
                ])->first();
                if (!$existingCountryQuestionnaire) {
                    $existingCountryQuestionnaire = self::createQuestionnaireCountry($questionnaire, $country, $assignee);
                }

                $userQuestionnaire = QuestionnaireUser::where([
                    ['user_id', $user->id],
                    ['questionnaire_country_id', $existingCountryQuestionnaire->id]
                ])->first();
                if (!$userQuestionnaire)
                {
                    QuestionnaireUser::create([
                        'user_id' => $user->id,
                        'questionnaire_country_id' => $existingCountryQuestionnaire->id
                    ]);

                    $user->notify(new NotifyUser([
                        'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
                        'subject' => 'EU Cybersecurity Index Survey - Invitation',
                        'markdown' => 'questionnaire-user-invitation',
                        'maildata' => [
                            'url' => env('APP_URL') . '/questionnaire/management'
                        ]
                    ]));
                }
            }

            array_push($questionnaire_users, $user->name);
        }

        return $questionnaire_users;
    }

    public static function createQuestionnaireCountry(Questionnaire $questionnaire, Country $country, User $user)
    {
        $questionnaire_country = null;

        $questionnaire_country = QuestionnaireCountry::create(
            [
                'default_assignee' => $user->id,
                'questionnaire_id' => $questionnaire->id,
                'country_id' => $country->id,
                'json_data' => '{}'
            ]
        );

        foreach ($questionnaire->indicators as $indicator)
        {
            $indicator = $indicator->indicator ?? $indicator;

            $count = IndicatorAccordion::where('indicator_id', $indicator->id)->count();
            if (!$count) {
                continue;
            }
            
            SurveyIndicator::updateOrCreateSurveyIndicator(
                [
                    'questionnaire_country_id' => $questionnaire_country->id,
                    'indicator_id' => $indicator->id,
                    'assignee' => $user->id,
                    'state_id' => 2,
                    'dashboard_state_id' => 2,
                    'deadline' => $questionnaire->deadline
                ]
            );
        }

        return $questionnaire_country;
    }

    public static function createQuestionnaireTemplate($year)
    {
        $retval = Artisan::call('export:survey-excel', ['--year' => $year]);

        if ($retval > 0) {
            return false;
        }

        return true;
    }

    public static function createQuestionnaireWithAnswers($year, $country)
    {
        $retval = Artisan::call('export:survey-excel', ['--year' => $year, '--country' => $country->id]);

        if ($retval > 0) {
            return false;
        }

        return true;
    }

    public static function publishQuestionnaire($questionnaire)
    {
        Questionnaire::find($questionnaire->id)->update([
            'published' => 1,
            'published_at' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }

    public static function sendReminderForPublishedQuestionnaire(Questionnaire $questionnaire)
    {
        $countryQuestionnaire = QuestionnaireCountry::with('user_questionnaires')->where('submitted_by', null)->where('questionnaire_id', $questionnaire->id)->get();

        foreach ($countryQuestionnaire as $country) {
            $user = User::find($country->user_questionnaires[0]->user_id);
            $user->notify(new NotifyUser([
                'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
                'subject' => 'EU Cybersecurity Index Survey - Invitation: Kind Reminder',
                'markdown' => 'questionnaire-user-invitation-reminder',
                'maildata' => [
                    'url' => env('APP_URL') . '/questionnaire/management'
                ]
            ]));
        }
    }

    public static function deleteQuestionnaire($id)
    {
        $questionnaire = Questionnaire::find($id);

        if (!$questionnaire->published)
        {
            $questionnaire->country_questionnaires()->delete();
            $questionnaire->indicators()->delete();

            return $questionnaire->delete();
        }

        return false;
    }
}
