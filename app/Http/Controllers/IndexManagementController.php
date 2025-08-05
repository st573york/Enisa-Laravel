<?php

namespace App\Http\Controllers;

use App\Jobs\IndexCalculation;
use App\HelperFunctions\IndexComparison;
use App\HelperFunctions\IndexConfigurationHelper;
use App\HelperFunctions\IndexConfigurationIndicatorHelper;
use App\HelperFunctions\IndexYearChoiceHelper;
use App\Models\Audit;
use App\Models\Area;
use App\Models\IndexConfiguration;
use App\Models\Index;
use App\Models\BaselineIndex;
use App\Models\Indicator;
use App\Models\Questionnaire;
use App\Models\Subarea;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class IndexManagementController extends Controller
{
    const ERROR_NOT_AUTHORIZED = 'You are not authorized for this action!';
    const ERROR_NOT_ALLOWED = 'The requested action is not allowed!';

    public function management()
    {
        return view('index.management');
    }

    public function viewIndexAndSurveyConfiguration()
    {
        $years = IndexYearChoiceHelper::getIndexConfigurationYearChoices();
        $year = $_COOKIE['index-year'];
        
        $publishedIndex = IndexConfiguration::getExistingPublishedConfigurationForYear($year);
        $publishedSurvey = Questionnaire::getExistingPublishedQuestionnaireForYear($year);
        $indicatorsWithSurvey = Indicator::getIndicatorsWithSurvey($year);
        $configurationExist = Area::where('year', $year)->first();
        
        return view('index.configuration', [
            'years' => $years,
            'publishedIndex' => (!is_null($publishedIndex) ? true : false),
            'publishedSurvey' => (!is_null($publishedSurvey) ? true : false),
            'canEditSurvey' => (is_null($publishedSurvey) && !empty($indicatorsWithSurvey)) ? true : false,
            'canPreviewSurvey' => (!empty($indicatorsWithSurvey)) ? true : false,
            'canDownloadConfiguration' => (is_null($configurationExist)) ? false : true
        ]);
    }

    public function showIndexAndSurveyConfigurationImport()
    {
        $year = $_COOKIE['index-year'];

        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $publishedIndex = IndexConfiguration::getExistingPublishedConfigurationForYear($year);
        if (!is_null($publishedIndex)) {
            return response()->json(['error' => self::ERROR_NOT_ALLOWED], 405);
        }

        return view('ajax.index-and-survey-import');
    }

    public function storeIndexAndSurveyConfigurationImport(Request $request)
    {
        $year = $_COOKIE['index-year'];
        $file = $request->file('file');
        $request['extension'] = ($file) ? strtolower($file->getClientOriginalExtension()) : null;

        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $publishedIndex = IndexConfiguration::getExistingPublishedConfigurationForYear($year);
        if (!is_null($publishedIndex)) {
            return response()->json(['error' => self::ERROR_NOT_ALLOWED], 405);
        }

        $validator = IndexConfigurationHelper::validateIndexPropertiesUpload($request);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        $current_year = date('Y');

        $path = storage_path() . '/app/index-properties/' . $current_year;

        File::ensureDirectoryExists($path);

        $originalName = $file->getClientOriginalName();
        $filename = time() . '_' . $originalName;

        $file->move($path, $filename);

        $excel = $path . '/' . $filename;

        try
        {
            $resp = IndexConfigurationHelper::importIndexProperties($year, $excel, $originalName);
            if ($resp['type'] == 'error') {
                return response()->json([$resp['type'] => $resp['msg']], $resp['status']);
            }
            
            return response()->json('ok', 200);
        }
        catch (\Exception $e)
        {
            Log::debug($e->getMessage());

            File::delete($excel);

            return response()->json(['error' => 'File could not be parsed correctly!'], 400);
        }
    }

    public function showIndexAndSurveyConfigurationClone()
    {
        $year = $_COOKIE['index-year'];

        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $publishedIndex = IndexConfiguration::getExistingPublishedConfigurationForYear($year);
        if (!is_null($publishedIndex)) {
            return response()->json(['error' => self::ERROR_NOT_ALLOWED], 405);
        }

        $published_indexes = IndexConfiguration::getPublishedConfigurations();

        return view('ajax.index-and-survey-clone', ['published_indexes' => $published_indexes->slice(0, -1)]);
    }

    public function storeIndexAndSurveyConfigurationClone(Request $request)
    {
        $inputs = $request->all();

        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $validator = IndexConfigurationHelper::validateIndexConfigurationClone($inputs);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        $index_data = IndexConfiguration::getIndexConfiguration($inputs['clone-index']);

        $publishedIndex = IndexConfiguration::getExistingPublishedConfigurationForYear($index_data->year);
        if (is_null($publishedIndex)) {
            return response()->json(['error' => self::ERROR_NOT_ALLOWED], 405);
        }

        $resp = IndexConfigurationHelper::cloneIndexConfiguration($index_data->year, $inputs['clone-survey']);
        if ($resp['type'] == 'error') {
            return response()->json([$resp['type'] => $resp['msg']], $resp['status']);
        }
        
        if ($inputs['clone-survey'])
        {
            $resp = IndexConfigurationIndicatorHelper::cloneSurveyConfiguration($index_data->year);
            if ($resp['type'] == 'error') {
                return response()->json([$resp['type'] => $resp['msg']], $resp['status']);
            }
        }

        Audit::setCustomAuditEvent(
            IndexConfiguration::find($index_data->id),
            ['event' => 'cloned', 'audit' => ['year' => $_COOKIE['index-year'], 'clone_index' => $index_data->year, 'clone_survey' => ($inputs['clone-survey'] ? 'Yes' : 'No')]]
        );

        return response()->json('ok', 200);
    }

    public function list()
    {
        $indexes = IndexConfiguration::getIndexConfigurations();

        foreach ($indexes as $index)
        {
            $user = User::withTrashed()->where('name', $index->user)->first();
            $index->user .= ($user->trashed() ? ' ' . config('constants.USER_INACTIVE') : '');
        }

        return response()->json(['data' => $indexes], 200);
    }

    public function createIndex()
    {
        return view('ajax.index-create', ['years' => config('constants.YEARS_TO_DATE_AND_NEXT')]);
    }

    public function storeIndex(Request $request)
    {
        $inputs = $request->all();
        $inputs['year'] = (isset($inputs['year']) && $inputs['year']) ? $inputs['year'] : '';

        $validator = IndexConfigurationHelper::validateInputsForCreate($inputs);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        if (!IndexConfigurationHelper::canCreateIndexConfiguration($inputs['year'])) {
            return response()->json(['warning' => 'Index for year ' . $inputs['year'] . ' cannot be created. Please <a href="/index/survey/configuration/management/">configure</a> index first!'], 403);
        }

        $inputs['json_data'] = IndexConfiguration::generateIndexConfigurationTemplate($inputs['year']);

        IndexConfigurationHelper::storeIndexConfiguration($inputs);

        return response()->json('ok', 200);
    }

    public function showIndex(IndexConfiguration $index)
    {
        return view('index.view', ['index' => $index, 'years' => config('constants.YEARS_TO_DATE_AND_NEXT')]);
    }

    public function getIndexJson(Request $request, IndexConfiguration $index)
    {
        $inputs = $request->all();
        $indexYear = (!is_null($inputs['indexYear'])) ? $inputs['indexYear'] : $index->year;

        $areas = Area::where('year', $indexYear)->get();
        $subareas = Subarea::where('year', $indexYear)->get();
        $indicators = Indicator::whereIn('category', ['survey', 'eurostat', 'manual'])->where('year', $indexYear)->get();

        $data = ['areas' => $areas, 'subareas' => $subareas, 'indicators' => $indicators, 'indexJson' => $index->json_data];

        return response()->json($data, 200);
    }

    public function getIndexTree(Request $request, IndexConfiguration $index)
    {
        $inputs = $request->all();
        $json_data = (isset($inputs['indexJson'])) ? $inputs['indexJson'] : $index->json_data;

        $tree = IndexComparison::prepareIndexConfigurationForTree($json_data, true);

        return view('ajax.index-tree', ['tree' => $tree, 'index' => $index]);
    }

    public function editIndex(Request $request, IndexConfiguration $index)
    {
        $inputs = $request->all();
        $inputs['name'] = (isset($inputs['name'])) ? $inputs['name'] : $index->name;
        $inputs['description'] = (isset($inputs['description'])) ? $inputs['description'] : $index->description;
        $inputs['year'] = (isset($inputs['year'])) ? $inputs['year'] : $index->year;
        $inputs['json_data'] = $index->json_data;

        $validator = IndexConfigurationHelper::validateInputsForEdit($inputs);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        $error = IndexConfigurationHelper::validateIndexConfigurationStatusAndData($index, $inputs);
        if ($error) {
            return response()->json(['error' => $error], 405);
        }

        IndexConfigurationHelper::updateIndexConfiguration($index, $inputs);

        return response()->json('ok', 200);
    }

    public function deleteIndex(IndexConfiguration $index)
    {
        if (!IndexConfigurationHelper::canDeleteIndexConfiguration($index)) {
            return response()->json(['error' => 'Index cannot be deleted as it is published.'], 405);
        }

        IndexConfigurationHelper::deleteIndexConfiguration($index);

        return response()->json('ok', 200);
    }

    public function calculateIndex(IndexConfiguration $index)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $latest_index_data = IndexConfiguration::getLatestPublishedConfiguration();
        if ($index->id != $latest_index_data->id) {
            return response()->json(['error' => self::ERROR_NOT_ALLOWED], 405);
        }

        Index::disableAuditing();
        BaselineIndex::disableAuditing();
        IndexCalculation::dispatch($index, Auth::user())->onQueue('calculation');
        Index::enableAuditing();
        BaselineIndex::enableAuditing();

        Audit::setCustomAuditEvent(
            IndexConfiguration::find($index->id),
            ['event' => 'updated', 'audit' => ['status' => 'Calculated Index']]
        );

        return response()->json('ok', 200);
    }
}
