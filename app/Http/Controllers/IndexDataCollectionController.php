<?php

namespace App\Http\Controllers;

use App\Jobs\ImportDataCollection;
use App\Jobs\ExternalDataCollection;
use App\HelperFunctions\IndexDataCollectionHelper;
use App\HelperFunctions\QuestionnaireCountryHelper;
use App\HelperFunctions\TaskHelper;
use App\Models\Audit;
use App\Models\IndexConfiguration;
use App\Models\Country;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use App\Models\EurostatIndicator;
use App\Models\Index;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class IndexDataCollectionController extends Controller
{
    const ERROR_NOT_AUTHORIZED = 'You are not authorized for this action!';
    const ERROR_NOT_ALLOWED = 'The requested action is not allowed!';
    const INDEXCALCULATIONTASK = 'IndexCalculation';
    const IMPORTDATACOLLECTIONTASK = 'ImportDataCollection';
    const EXTERNALDATACOLLECTIONTASK = 'ExternalDataCollection';

    public function viewDataCollection()
    {
        $latest_index_data = IndexConfiguration::getLatestPublishedConfiguration();
        $loaded_index_data = IndexConfiguration::getLoadedPublishedConfiguration($latest_index_data);
        $task = TaskHelper::getTask([
            'type' => self::INDEXCALCULATIONTASK,
            'index_configuration_id' => $loaded_index_data->id
        ]);

        return view(
            'index.data-collection',
            [
                'loaded_index_data' => $loaded_index_data,
                'latest_index_data' => $latest_index_data,
                'task' => $task
            ]
        );
    }

    public function listDataCollection(IndexConfiguration $index)
    {
        $questionnaire = Questionnaire::getPublishedQuestionnaires($index);
        $questionnaire_id = $questionnaire->value('id') ?? null;
        $countries = IndexDataCollectionHelper::getDataCollectionCountries($index, $questionnaire_id);
        $imported_indicators = Indicator::where('category', 'manual')->where('year', $index->year)->count();
        $eurostat_indicators = Indicator::where('category', 'eurostat')->where('year', $index->year)->count();
        
        foreach ($countries as $country)
        {
            $country->id = $country->questionnaire_country_id;

            $country->imported_indicators_approved = IndexDataCollectionHelper::getDataCollectionIndicators($index, $country, 'manual');
            $country->imported_indicators = $imported_indicators;
            
            $questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $questionnaire_id)->where('country_id', $country->country_id)->first();

            if (!is_null($questionnaire_country))
            {
                QuestionnaireCountryHelper::getQuestionnaireCountryData($questionnaire_country);

                $country->questionnaire_indicators = $questionnaire_country->indicators;
                $country->questionnaire_indicators_final_approved = $questionnaire_country->indicators_final_approved;
                $country->questionnaire_indicators_percentage_final_approved = $questionnaire_country->percentage_final_approved;
            }

            $country->eurostat_indicators_approved = IndexDataCollectionHelper::getDataCollectionIndicators($index, $country, 'eurostat');
            $country->eurostat_indicators = $eurostat_indicators;
        }

        return response()->json(['data' => $countries], 200);
    }

    public function viewImportDataCollection()
    {
        $published_indexes = IndexConfiguration::getPublishedConfigurations();
        $latest_index_data = IndexConfiguration::getLatestPublishedConfiguration();
        $loaded_index_data = IndexConfiguration::getLoadedPublishedConfiguration($latest_index_data);

        $inputs = [
            'index' => $loaded_index_data,
            'category' => [
                'manual',
                'eu-wide'
            ]
        ];
        $indicators = [];
        $countries = [];

        $task = TaskHelper::getTask([
            'type' => self::IMPORTDATACOLLECTIONTASK,
            'index_configuration_id' => $loaded_index_data->id
        ]);

        $data = IndexDataCollectionHelper::getIndicatorValueData($inputs);

        $filter_data = IndexDataCollectionHelper::getFilterData($data);
        if (!empty($filter_data)) {
            list($indicators, $countries) = $filter_data;
        }

        return view('index.import-data-collection', [
            'published_indexes' => $published_indexes,
            'loaded_index_data' => $loaded_index_data,
            'latest_index_data' => $latest_index_data,
            'task' => $task,
            'indicators' => collect($indicators)->sort(),
            'countries' => collect($countries)->sort(),
            'table_data' => $data
        ]);
    }

    public function listImportDataCollection(Request $request, IndexConfiguration $index)
    {
        $inputs = $request->all();
        $inputs['index'] = $index;
        $inputs['category'] = [
            'manual',
            'eu-wide'
        ];

        $data = IndexDataCollectionHelper::getIndicatorValueData($inputs);

        return response()->json(['data' => $data], 200);
    }

    public function showImportDataCollection(IndexConfiguration $index)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $task = TaskHelper::getTask([
            'type' => self::IMPORTDATACOLLECTIONTASK,
            'index_configuration_id' => $index->id
        ]);

        $latest_index_data = IndexConfiguration::getLatestPublishedConfiguration();
        if ($index->id != $latest_index_data->id ||
            ($task && $task->status_id == 1))
        {
            return response()->json(['error' => self::ERROR_NOT_ALLOWED], 405);
        }

        return view('ajax.index-data-collection-management');
    }

    public function storeImportDataCollection(Request $request, IndexConfiguration $index)
    {
        $file = $request->file('file');
        $request['extension'] = ($file) ? strtolower($file->getClientOriginalExtension()) : null;

        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $task = TaskHelper::getTask([
            'type' => self::IMPORTDATACOLLECTIONTASK,
            'index_configuration_id' => $index->id
        ]);

        $latest_index_data = IndexConfiguration::getLatestPublishedConfiguration();
        if ($index->id != $latest_index_data->id ||
            ($task && $task->status_id == 1))
        {
            return response()->json(['error' => self::ERROR_NOT_ALLOWED], 405);
        }

        $validator = IndexDataCollectionHelper::validateInputs($request);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        try
        {
            $current_year = date('Y');

            $path = storage_path() . '/app/import-data/' . $current_year;

            File::ensureDirectoryExists($path);

            $originalName = htmlspecialchars($file->getClientOriginalName());
            $filename = time() . '_' . $originalName;

            $file->move($path, $filename);

            $excel = $path . '/' . $filename;

            IndicatorValue::disableAuditing();
            ImportDataCollection::dispatch($index, Auth::user(), $excel);
            IndicatorValue::enableAuditing();

            Audit::setCustomAuditEvent(
                IndexConfiguration::find($index->id),
                ['event' => 'imported', 'audit' => ['file' => $originalName]]
            );

            return response()->json('ok', 200);
        }
        catch (\Exception $e)
        {
            Log::debug($e->getMessage());

            File::delete($excel);

            return response()->json(['error' => 'File could not be parsed correctly!'], 400);
        }
    }

    public function discardImportDataCollection(IndexConfiguration $index)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $task = TaskHelper::getTask([
            'type' => self::IMPORTDATACOLLECTIONTASK,
            'index_configuration_id' => $index->id
        ]);

        $latest_index_data = IndexConfiguration::getLatestPublishedConfiguration();
        if ($index->id != $latest_index_data->id ||
            ($task && $task->status_id == 1))
        {
            return response()->json(['error' => self::ERROR_NOT_ALLOWED], 405);
        }

        IndexDataCollectionHelper::discardImportDataCollection($index);

        TaskHelper::deleteTask($task);

        Audit::setCustomAuditEvent(
            IndexConfiguration::find($index->id),
            ['event' => 'updated', 'audit' => ['status' => 'Discarded Import Data']]
        );

        return response()->json('ok', 200);
    }

    public function approveIndexByCountry(IndexConfiguration $index, Country $country)
    {
        $country_index = $index->index()->where('country_id', $country->id)->first();

        $task = TaskHelper::getTask([
            'type' => self::INDEXCALCULATIONTASK,
            'index_configuration_id' => $index->id
        ]);

        if (($task && $task->status_id == 1) ||
            $country_index->status_id != 2)
        {
            return response()->json(['error' => self::ERROR_NOT_ALLOWED], 405);
        }

        IndexDataCollectionHelper::approveIndexByCountry($index, $country);

        Audit::setCustomAuditEvent(
            Country::find($country->id),
            ['event' => 'updated', 'audit' => ['status' => 'Approved', 'index' => $index->name]]
        );

        return response()->json('ok', 200);
    }

    public function viewExternalDataCollection()
    {
        $loaded_index_data = IndexConfiguration::getLoadedPublishedConfiguration();

        $data = [];
        $inputs = [
            'index' => $loaded_index_data,
            'category' => 'eurostat'
        ];
        $indicators = [];
        $countries = [];

        $task = TaskHelper::getTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'index_configuration_id' => $loaded_index_data->id
        ]);

        if ($task)
        {
            if ($task->status_id == 2) {
                $data = IndexDataCollectionHelper::getEurostatData($inputs);
            }
            elseif ($task->status_id == 4) {
                $data = IndexDataCollectionHelper::getIndicatorValueData($inputs);
            }

            $filter_data = IndexDataCollectionHelper::getFilterData($data);
            if (!empty($filter_data)) {
                list($indicators, $countries) = $filter_data;
            }
        }

        return view('index.external-data-collection', [
            'loaded_index_data' => $loaded_index_data,
            'task' => $task,
            'indicators' => collect($indicators)->sort(),
            'countries' => collect($countries)->sort(),
            'table_data' => $data
        ]);
    }

    public function listExternalDataCollection(Request $request, IndexConfiguration $index)
    {
        $inputs = $request->all();
        $data = [];
        $inputs['index'] = $index;
        $inputs['category'] = 'eurostat';

        $task = TaskHelper::getTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'index_configuration_id' => $index->id
        ]);

        if ($task)
        {
            if ($task->status_id == 2) {
                $data = IndexDataCollectionHelper::getEurostatData($inputs);
            }
            elseif ($task->status_id == 4) {
                $data = IndexDataCollectionHelper::getIndicatorValueData($inputs);
            }
        }

        return response()->json(['data' => $data], 200);
    }

    public function collectExternalDataCollection(IndexConfiguration $index)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $latest_index_data = IndexConfiguration::getLatestPublishedConfiguration();
        if ($index->id != $latest_index_data->id) {
            return response()->json(['error' => self::ERROR_NOT_ALLOWED], 405);
        }

        ExternalDataCollection::dispatch($index, Auth::user())->onQueue('external');

        Audit::setCustomAuditEvent(
            IndexConfiguration::find($index->id),
            ['event' => 'updated', 'audit' => ['status' => 'Collected External Data']]
        );

        return response()->json('ok', 200);
    }

    public function discardExternalDataCollection(IndexConfiguration $index)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $task = TaskHelper::getTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'index_configuration_id' => $index->id
        ]);

        $latest_index_data = IndexConfiguration::getLatestPublishedConfiguration();
        if ($index->id != $latest_index_data->id ||
            ($task && in_array($task->status_id, [1, 4])))
        {
            return response()->json(['error' => self::ERROR_NOT_ALLOWED], 405);
        }

        IndexDataCollectionHelper::discardExternalDataCollection($index);

        if (isset($task->payload['last_external_data_approved_at'])) {
            TaskHelper::updateOrCreateTask([
                'type' => self::EXTERNALDATACOLLECTIONTASK,
                'user_id' => (isset($task->payload['last_external_data_approved_by'])) ? $task->payload['last_external_data_approved_by'] : null,
                'status_id' => 4,
                'index_configuration_id' => $index->id
            ]);
        }
        else {
            TaskHelper::deleteTask($task);
        }

        Audit::setCustomAuditEvent(
            IndexConfiguration::find($index->id),
            ['event' => 'updated', 'audit' => ['status' => 'Discarded External Data']]
        );

        return response()->json('ok', 200);
    }

    public function approveExternalDataCollection(Request $request, IndexConfiguration $index)
    {
        $inputs = $request->all();

        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        $task = TaskHelper::getTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'index_configuration_id' => $index->id
        ]);

        $latest_index_data = IndexConfiguration::getLatestPublishedConfiguration();
        if ($index->id != $latest_index_data->id ||
            ($task && $task->status_id != 2))
        {
            return response()->json(['error' => self::ERROR_NOT_ALLOWED], 405);
        }

        if ($inputs['action'] == 'approve-selected')
        {
            $eurostatIndicatorIds = array_filter(explode(',', $inputs['datatable-selected']));

            $eurostatIndicators = EurostatIndicator::whereIn('id', $eurostatIndicatorIds)->get();
        }
        elseif ($inputs['action'] == 'approve-all') {
            $eurostatIndicators = EurostatIndicator::get();
        }

        $approvedCountries = Index::where('status_id', 3)->where('index_configuration_id', $index->id)->pluck('country_id')->toArray();
        $indicators = Indicator::where('category', 'eurostat')->where('year', $index->year)->get()->keyBy('identifier')->toArray();

        IndicatorValue::disableAuditing();

        foreach ($eurostatIndicators as $eurostatIndicator)
        {
            if (isset($indicators[$eurostatIndicator->identifier]))
            {
                if (in_array($eurostatIndicator->country_id, $approvedCountries)) {
                    continue;
                }

                IndicatorValue::updateOrCreateIndicatorValue([
                    'indicator_id' => $indicators[$eurostatIndicator->identifier]['id'],
                    'country_id' => $eurostatIndicator->country_id,
                    'year' => $indicators[$eurostatIndicator->identifier]['year'],
                    'value' => $eurostatIndicator->value
                ]);
            }
        }

        IndicatorValue::enableAuditing();

        TaskHelper::updateOrCreateTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'status_id' => 4,
            'index_configuration_id' => $index->id,
            'payload' => [
                'last_external_data_approved_by' => Auth::user()->id,
                'last_external_data_approved_at' => Carbon::now()->format('d-m-Y H:i:s')
            ]
        ]);

        Audit::setCustomAuditEvent(
            IndexConfiguration::find($index->id),
            ['event' => 'updated', 'audit' => ['status' => 'Approved External Data']]
        );

        return response()->json('ok', 200);
    }
}
