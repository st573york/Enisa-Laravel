<?php

namespace App\Http\Controllers;

use App\HelperFunctions\QuestionnaireCountryHelper;
use App\HelperFunctions\UserPermissions;
use App\HelperFunctions\TaskHelper;
use App\Jobs\ExportData;
use App\Jobs\ExportIndexProperties;
use App\Jobs\ExportMSRawData;
use App\Jobs\ExportReportData;
use App\Jobs\ExportSurveyExcel;
use App\Models\Audit;
use App\Models\Country;
use App\Models\IndexConfiguration;
use App\Models\Indicator;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DownloadManagerController extends Controller
{
    public function downloadExportData(Request $request)
    {
        $inputs = $request->all();

        $task = Task::where('type', $inputs['task'])->where('user_id', Auth::user()->id)->first();

        if ($task)
        {
            if ($task->status_id == 2)
            {
                $filename = $task->payload['filename'];
                $path = storage_path() . '/app/' . $filename;

                if (isset($task->payload['index']))
                {
                    $sources = $task->payload['sources'];
                    if (count($sources) == 1 &&
                        $sources[0] == 'survey')
                    {
                        $model = Questionnaire::where('index_configuration_id', $task->payload['index'])->first();
                    }
                    else {
                        $model = IndexConfiguration::find($task->payload['index']);
                    }
                }
                elseif (isset($task->payload['questionnaire'])) {
                    $model = Questionnaire::find($task->payload['questionnaire']);
                }
                elseif (isset($task->payload['year'])) {
                    $model = Task::find($task->id);
                }

                Audit::setCustomAuditEvent(
                    $model,
                    ['event' => 'exported', 'audit' => ['file' => $filename]]
                );

                TaskHelper::deleteTask($task);

                return response()->download($path)->deleteFileAfterSend(true);
            }
            elseif ($task->status_id == 3)
            {
                TaskHelper::deleteTask($task);

                return response()->json(['error' => 'File generation error!'], 400);
            }

            return response()->json(['error' => 'File processing...'], 404);
        }

        return response()->json(['error' => 'File not found!'], 410);
    }

    public function createExportData(Request $request, $id)
    {
        $inputs = $request->all();
        $index = null;
        $entity = null;
        $indexDataFlag = false;

        if ($inputs['requestLocation'] == 'index') {
            $index = IndexConfiguration::find($id);
        } else {
            $entity = Questionnaire::find($id);
        }
        if ($entity) {
            $index = $entity->configuration;
        }
        if ($index->draft) {
            return response()->json(['error' => 'Index is not published!'], 400);
        }

        $countries = $inputs['countries'];
        $availableCountries = UserPermissions::getUserCountries();

        if ($countries == 'all') {
            $countries = $availableCountries;
        }
        else
        {
            if (!is_array($countries)) {
                $countries = [$countries];
            }

            $countries = array_intersect($countries, $availableCountries);
        }

        if (empty($countries)) {
            return response()->json(['error' => 'No countries found for this user!'], 400);
        }

        $sources = $inputs['sources'];
        if ($sources == 'all')
        {
            $indexDataFlag = true;
            $sources = Indicator::where('category', '!=', 'eu-wide')->distinct()->pluck('category')->toArray();
        }
        if (!is_array($sources)) {
            $sources = [$sources];
        }

        ExportData::dispatch($index, Auth::user(), $countries, $sources, $indexDataFlag);

        return  response()->json('ok', 200);
    }

    public function createExportReportData(Request $request, $country = null)
    {
        $inputs = $request->all();
        
        ExportReportData::dispatch(
            $inputs['year'],
            Auth::user(),
            $country
        );

        return response()->json('ok', 200);
    }

    public function createExportMSRawData(Request $request, Country $country)
    {
        $inputs = $request->all();
        
        ExportMSRawData::dispatch(
            $inputs['year'],
            Auth::user(),
            $country
        );

        return response()->json('ok', 200);
    }

    public function createExportSurveyExcel(Request $request, Questionnaire $questionnaire = null)
    {
        $inputs = $request->all();
        $type = $inputs['type'];

        $indicators = [];
        $country = null;

        if ($type == 'survey_template')
        {
            if (isset($inputs['questionnaire_country_id']))
            {
                $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($inputs['questionnaire_country_id']);

                $indicators = $data['indicators_assigned_exact']['identifier'];
            }
        }
        elseif ($type == 'survey_with_answers')
        {
            $questionnaire_country = QuestionnaireCountry::find($inputs['questionnaire_country_id']);
            $country = $questionnaire_country->country;
        }

        ExportSurveyExcel::dispatch(
            Auth::user(),
            $questionnaire,
            (!is_null($questionnaire) ? $questionnaire->year : $_COOKIE['index-year']),
            $indicators,
            $country,
            $type
        );

        return response()->json('ok', 200);
    }

    public function createExportIndexProperties($year)
    {
        ExportIndexProperties::dispatch($year, Auth::user());

        return response()->json('ok', 200);
    }
}
