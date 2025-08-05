<?php

namespace App\Http\Controllers;

use App\HelperFunctions\IndexYearChoiceHelper;
use App\HelperFunctions\IndexReportsHelper;
use App\HelperFunctions\UserPermissions;
use App\Models\Audit;
use App\Models\BaselineIndex;
use App\Models\Index;
use App\Models\IndexConfiguration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class IndexReportsAndDataController extends Controller
{
    public function viewExportData()
    {
        $loaded_index_data = IndexConfiguration::getLoadedPublishedConfiguration();
        $years = IndexYearChoiceHelper::getIndexYearChoices();
        $indices = IndexReportsHelper::getIndiceReports($loaded_index_data);
        $baseline_index = BaselineIndex::where('index_configuration_id', $loaded_index_data->id)->first();

        $export_data = [];
        if ($loaded_index_data->ms_published) {
            array_push($export_data, [
                'type' => 'ms_report',
                'country' => true,
                'title' => 'MS Report'
            ]);
        }
        if ($loaded_index_data->eu_published) {
            array_push($export_data, [
                'type' => 'eu_report',
                'country' => false,
                'title' => 'EU Report'
            ]);
        }
        if ($loaded_index_data->ms_published)
        {
            array_push($export_data, [
                'type' => 'ms_raw_data',
                'country' => true,
                'title' => 'MS Raw Data'
            ]);

            if (Auth::user()->isAdmin()) {
                array_push($export_data, [
                    'type' => 'ms_results',
                    'country' => false,
                    'title' => 'MS Results'
                ]);
            }
        }

        File::ensureDirectoryExists(storage_path() . '/app/reports/' . $loaded_index_data->year);
        
        return view('components.export_data', [
            'loaded_index_data' => $loaded_index_data,
            'years' => $years,
            'indices' => (!empty($indices)) ? $indices[$loaded_index_data->year] : [],
            'baseline_index' => $baseline_index,
            'export_data' => $export_data]);
    }

    public function downloadMsReport(Index $index)
    {
        $availableIndices = UserPermissions::getUserAvailableIndicesByYear($index->configuration->year);
        if (!$availableIndices->contains($index)) {
            return abort(403, 'You are not authorized!');
        }
        
        $filename = 'EUCSI-MS-report-' . $index->configuration->year . '-' . $index->country->iso . '.pdf';

        Audit::setCustomAuditEvent(
            IndexConfiguration::getLatestPublishedConfiguration(),
            ['event' => 'exported', 'audit' => ['file' => $filename]]
        );
        
        $inputFile = storage_path() . '/app/reports/' . $index->configuration->year . '/' . $filename;

        return response()->download($inputFile, $filename, ['Content-Type' => 'application/force-download']);
    }

    public function downloadEuReport()
    {
        $loaded_index_data = IndexConfiguration::getLoadedPublishedConfiguration();

        $filename = 'EUCSI-EU-report-' . $loaded_index_data->year . '.pdf';

        Audit::setCustomAuditEvent(
            IndexConfiguration::getLatestPublishedConfiguration(),
            ['event' => 'exported', 'audit' => ['file' => $filename]]
        );
        
        $inputFile = storage_path() . '/app/reports/' . $loaded_index_data->year . '/' . $filename;

        return response()->download($inputFile, $filename, ['Content-Type' => 'application/force-download']);
    }

    public function downloadMsRawData(Index $index)
    {
        $availableIndices = UserPermissions::getUserAvailableIndicesByYear($index->configuration->year);
        if (!$availableIndices->contains($index)) {
            return abort(403, 'You are not authorized!');
        }
        
        $filename = 'EUCSI-MS-raw-data-' . $index->configuration->year . '-' . $index->country->iso . '.xlsx';

        Audit::setCustomAuditEvent(
            IndexConfiguration::getLatestPublishedConfiguration(),
            ['event' => 'exported', 'audit' => ['file' => $filename]]
        );
        
        $inputFile = storage_path() . '/app/reports/' . $index->configuration->year . '/' . $filename;

        return response()->download($inputFile, $filename, ['Content-Type' => 'application/force-download']);
    }

    public function downloadMsResults()
    {
        $loaded_index_data = IndexConfiguration::getLoadedPublishedConfiguration();
        $year = $loaded_index_data->year;

        $filename = ($year == '2023') ? 'CSresults' : 'EUCSI-results';
        $filename_to_download = 'EUCSI-results-' . $year . '.xlsx';

        Audit::setCustomAuditEvent(
            IndexConfiguration::getLatestPublishedConfiguration(),
            ['event' => 'exported', 'audit' => ['file' => $filename_to_download]]
        );
        
        $inputFile = storage_path() . '/app/index_calculations/' . $year . '/' . $loaded_index_data->id . ($year == '2023' ? '/files/' : '/results/') . $filename . '.xlsx';

        return response()->download($inputFile, $filename_to_download, ['Content-Type' => 'application/force-download']);
    }

    public function getIndexReportJson(Index $index)
    {
        $loaded_index_data = IndexConfiguration::getLoadedPublishedConfiguration();

        $availableIndices = UserPermissions::getUserAvailableIndicesByYear($loaded_index_data->year);
        if (!$availableIndices->contains($index)) {
            return abort(403, 'You are not authorized!');
        }

        Audit::setCustomAuditEvent(
            $index->configuration,
            ['event' => 'viewed', 'audit' => ['country_report' => $index->country->name . ' ' . $index->configuration->year]]
        );

        $flagFile = '/images/countries/flags/' . $index->country->name . '.png';
        $mapFile = '/images/countries/maps/' . $index->country->name . '.svg';
        $mapSvgPath = file_get_contents(public_path() . $mapFile);

        return view(
            'components.reports',
            [
                'index' => $index->id,
                'data' => $index->report_json[0],
                'country' => $index->country,
                'mapSvgPath' => $mapSvgPath,
                'flagFile' => $flagFile
            ]
        );
    }

    public function getEuReportJson()
    {
        $loaded_index_data = IndexConfiguration::getLoadedPublishedConfiguration();
        $baseline_index = BaselineIndex::where('index_configuration_id', $loaded_index_data->id)->first();

        return view('components.report_eu', ['data' => $baseline_index->report_json[0]]);
    }

    public function getIndexReportChartData(Index $index)
    {
        $loaded_index_data = IndexConfiguration::getLoadedPublishedConfiguration();

        $availableIndices = UserPermissions::getUserAvailableIndicesByYear($loaded_index_data->year);
        if (!$availableIndices->contains($index)) {
            return abort(403, 'You are not authorized!');
        }

        $barChartData = IndexReportsHelper::getReportChartData($index);

        return response()->json($barChartData, 200);
    }

    public function getEUReportChartData()
    {
        $barChartData = IndexReportsHelper::getEUReportChartData();

        return response()->json($barChartData, 200);
    }
}
