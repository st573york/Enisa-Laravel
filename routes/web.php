<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexManagementController;
use App\Http\Controllers\IndexConfigurationAreaController;
use App\Http\Controllers\IndexConfigurationIndicatorController;
use App\Http\Controllers\IndexDataCollectionController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\IndexComparisonController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\QuestionnaireCountryController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\MyAccountController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\DocumentLibraryController;
use App\Http\Controllers\DownloadManagerController;
use App\Http\Controllers\IndexConfigurationSubareaController;
use App\Http\Controllers\IndexReportsAndDataController;
use App\Http\Controllers\InvitationManagementController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/index/access');
});

$middleware = (env('APP_ENV') === 'test') ? 'auth' : 'eulogin';
Route::middleware($middleware)->group(function () {

    Route::controller(DownloadManagerController::class)->prefix('export')->middleware('admin.poc')->group(function () {
        Route::get('/data/download', 'downloadExportData');
        Route::middleware('XssSanitizer')->group(function () {
            Route::middleware('admin.poc')->group(function () {
                Route::post('/data/create/{index}', 'createExportData');
                Route::post('/properties/create/{year}', 'createExportIndexProperties');
            });
            Route::post('/reportdata/create/{country?}', 'createExportReportData');
            Route::post('/msrawdata/create/{country}', 'createExportMSRawData');
            Route::post('/surveyexcel/create/{questionnaire?}', 'createExportSurveyExcel');
        });
    });

    Route::middleware('admin')->group(function () {
        Route::controller(IndexManagementController::class)->prefix('index')->group(function () {
            Route::get('/management', 'management');
            Route::get('/list', 'list');
            Route::get('/create', 'createIndex');
            Route::get('/show/{index}', 'showIndex');
            Route::get('/json/{index}', 'getIndexJson');
            Route::get('/survey/configuration/management', 'viewIndexAndSurveyConfiguration');
            Route::get('/configuration/import/show', 'showIndexAndSurveyConfigurationImport');
            Route::get('/configuration/clone/show', 'showIndexAndSurveyConfigurationClone');
            Route::middleware('XssSanitizer')->group(function () {
                Route::post('/add/node/{type}', 'addNodeModal');
                Route::post('/tree/{index}', 'getIndexTree');
                Route::post('/store', 'storeIndex');
                Route::post('/edit/{index}', 'editIndex');
                Route::post('/delete/{index}', 'deleteIndex');
                Route::post('/configuration/import/store', 'storeIndexAndSurveyConfigurationImport');
                Route::post('/configuration/clone/store', 'storeIndexAndSurveyConfigurationClone');
                Route::post('/datacollection/calculate/{index}', 'calculateIndex');
            });
        });

        Route::controller(IndexConfigurationAreaController::class)->prefix('index/area')->group(function () {
            Route::get('/list', 'list');
            Route::get('/create', 'createOrShowArea');
            Route::get('/show/{area}', 'showArea');
            Route::middleware('XssSanitizer')->group(function () {
                Route::post('/store', 'storeArea');
                Route::post('/update/{area}', 'updateArea');
                Route::post('/delete/{area}', 'deleteArea');
            });
        });

        Route::controller(IndexConfigurationSubareaController::class)->prefix('index/subarea')->group(function () {
            Route::get('/list', 'list');
            Route::get('/create', 'createOrShowSubarea');
            Route::get('/show/{subarea}', 'showSubarea');
            Route::middleware('XssSanitizer')->group(function () {
                Route::post('/store', 'storeSubarea');
                Route::post('/update/{subarea}', 'updateSubarea');
                Route::post('/delete/{subarea}', 'deleteSubarea');
            });
        });

        Route::controller(IndexConfigurationIndicatorController::class)->prefix('index/indicator')->group(function () {
            Route::get('/list', 'list');
            Route::get('/create', 'createOrShowIndicator');
            Route::get('/show/{indicator}', 'showIndicator');
            Route::get('/survey/{indicator}', 'getIndicatorSurvey');
            Route::middleware('XssSanitizer')->group(function () {
                Route::post('/store', 'storeIndicator');
                Route::post('/update/{indicator}', 'updateIndicator');
                Route::post('/delete/{indicator}', 'deleteIndicator');
                Route::post('/order/update', 'updateIndicatorsOrder');
                Route::post('/survey/store/{indicator}', 'storeIndicatorSurvey');
                Route::post('/survey/load/{indicator}', 'loadIndicatorSurvey');
            });
        });

        Route::controller(IndexDataCollectionController::class)->prefix('index/datacollection')->group(function () {
            Route::get('/', 'viewDataCollection');
            Route::get('/list/{index}', 'listDataCollection');
            Route::get('/importdata', 'viewImportDataCollection');
            Route::get('/importdata/list/{index}', 'listImportDataCollection');
            Route::get('/importdata/show/{index}', 'showImportDataCollection');
            Route::get('/external', 'viewExternalDataCollection');
            Route::get('/external/list/{index}', 'listExternalDataCollection');
            Route::middleware('XssSanitizer')->group(function () {
                Route::post('/importdata/store/{index}', 'storeImportDataCollection');
                Route::post('/importdata/discard/{index}', 'discardImportDataCollection');
                Route::post('/approve/{index}/{country}', 'approveIndexByCountry');
                Route::post('/external/collect/{index}', 'collectExternalDataCollection');
                Route::post('/external/discard/{index}', 'discardExternalDataCollection');
                Route::post('/external/approve/{index}', 'approveExternalDataCollection');
            });
        });

        Route::controller(AuditController::class)->prefix('audit')->group(function () {
            Route::get('/', 'view');
            Route::get('/list', 'list');
            Route::get('/changes/show', 'showChanges');
            Route::get('/changes/list/{audit}', 'listChanges');
        });
    });

    Route::controller(UserManagementController::class)->prefix('user')->middleware('admin.poc')->group(function () {
        Route::get('/management', 'management');
        Route::get('/list', 'list');
        Route::get('/single/edit/{user}', 'getUser');
        Route::get('/multiple/edit', 'getUsers');
        Route::middleware('XssSanitizer')->group(function () {
            Route::post('/block/toggle/{user}', 'toggleBlock');
            Route::post('/single/update/{user}', 'updateUser');
            Route::post('/multiple/update', 'updateUsers');
            Route::post('/single/delete/{user}', 'deleteUser');
            Route::post('/multiple/delete', 'deleteUsers');
        });
    });

    Route::controller(InvitationManagementController::class)->prefix('invitation')->middleware('admin.ppoc')->group(function () {
        Route::get('/management', 'management');
        Route::get('/list', 'list');
        Route::get('/create', 'createInvitation');
        Route::middleware('XssSanitizer')->group(function () {
            Route::post('/store', 'storeInvitation');
        });
    });

    Route::controller(IndexComparisonController::class)->prefix('index')->group(function () {
        Route::get('/access/{year?}', 'view');
        Route::get('/render/slider/{year}', 'renderSliderChart');

        Route::middleware('XssSanitizer')->group(function () {
            Route::get('/configurations/get/{node}/{year}', 'indices');
            Route::get('/sunburst/get', 'getSunburstData');
        });
    });

    Route::controller(IndexReportsAndDataController::class)->prefix('index')->middleware('admin.poc')->group(function () {
        Route::get('/report/export_data', 'viewExportData');
        Route::get('/report/json/{index}', 'getIndexReportJson');
        Route::get('/report/json_eu', 'getEuReportJson');
        Route::get('/report/chartData/{index}', 'getIndexReportChartData');
        Route::get('/report/chartEUData', 'getEUReportChartData');
        Route::get('/report/download_ms/{index}', 'downloadMsReport');
        Route::get('/report/download_eu', 'downloadEuReport');
        Route::get('/data/download_ms_raw_data/{index}', 'downloadMsRawData');
        Route::get('/data/download_ms_results', 'downloadMsResults');
    });

    Route::controller(QuestionnaireController::class)->prefix('questionnaire')->middleware('admin')->group(function () {
        Route::get('/admin/management', 'viewQuestionnaires');
        Route::get('/admin/management/list', 'listQuestionnaires');
        Route::get('/create', 'createOrShowQuestionnaire');
        Route::get('/users/{questionnaire}', 'listUsers');
        Route::get('/publish/show/{questionnaire}', 'showOrPublishQuestionnaire');
        Route::get('/show/{questionnaire}', 'showQuestionnaire');
        Route::middleware('XssSanitizer')->group(function () {
            Route::post('/store', 'storeQuestionnaire');
            Route::post('/update/{questionnaire}', 'updateQuestionnaire');
            Route::post('/publish/create/{questionnaire}', 'createUserQuestionnaire');
            Route::post('/sendreminder/{questionnaire}', 'sendReminderForPublishedQuestionnaire');
            Route::post('/delete/{questionnaire}', 'deleteQuestionnaire');
        });
    });

    Route::controller(QuestionnaireCountryController::class)->prefix('questionnaire')->middleware('admin.poc.operator')->group(function () {
        Route::get('/preview/{indicator?}', 'previewQuestionnaire');
        Route::middleware('poc.operator')->group(function () {
            Route::get('/management', 'viewUserQuestionnaires');
        });
        Route::middleware('admin')->group(function () {
            Route::get('/admin/dashboard/{questionnaire}', 'viewQuestionnaireCountriesDashboard');
            Route::get('/admin/dashboard/list/{questionnaire}', 'listQuestionnaireCountriesDashboard');
            Route::get('/admin/dashboard/indicatorvalues/{questionnaire}', 'viewQuestionnaireCountryIndicatorValues');
            Route::get('/admin/dashboard/indicatorvalues/list/{questionnaire}', 'listQuestionnaireCountryIndicatorValues');
            Route::middleware('XssSanitizer')->group(function () {
                Route::post('/finalise', 'finaliseSurvey');
                Route::post('/admin/dashboard/indicatorvalues/calculate/{questionnaire}', 'calculateQuestionnaireCountryIndicatorValues');
            });
        });
        Route::middleware('admin.poc')->group(function () {
            Route::get('/dashboard/management/{questionnaire}', 'viewQuestionnaireCountryDashboard');
            Route::get('/dashboard/management/list/{questionnaire?}', 'listQuestionnaireCountryDashboard');
            Route::middleware(\App\Http\Middleware\UserIsAuthorizedForSummaryData::class)->group(function () {
                Route::get('/dashboard/summarydata/{questionnaire}', 'viewQuestionnaireCountrySummaryData');
            });
            Route::get('/indicator/get/{indicator}', 'getQuestionnaireCountryIndicatorInfo');
            Route::middleware('poc')->group(function () {
                Route::get('/indicator/single/edit/{indicator}', 'getQuestionnaireCountryIndicator');
                Route::get('/indicator/multiple/edit', 'getQuestionnaireCountryIndicators');
            });
        });
        Route::middleware('poc.operator')->group(function () {
            Route::get('/offline/validate/{questionnaire}', 'validateQuestionnaireOffline');
        });
        Route::middleware('XssSanitizer')->group(function () {
            Route::post('/view/{questionnaire}', 'viewQuestionnaire');
            Route::middleware('poc.operator')->group(function () {
                Route::post('/indicator/validate/{questionnaire}', 'validateQuestionnaireIndicator');
                Route::post('/save/{questionnaire}', 'saveQuestionnaire');
                Route::post('/upload/{questionnaire}', 'uploadQuestionnaire');
                Route::post('/data/load/{questionnaire}', 'loadSurveyIndicatorData');
                Route::post('/data/reset/{questionnaire}', 'resetSurveyIndicatorData');
            });
            Route::middleware('admin.poc')->group(function () {
                Route::post('/indicator/single/update/{indicator}', 'updateQuestionnaireCountryIndicator');
                Route::post('/indicator/multiple/update', 'updateQuestionnaireCountryIndicators');
                Route::post('/indicator/request_changes/{indicator}', 'requestChangesQuestionnaireCountryIndicator');
                Route::post('/indicator/discard_requested_changes/{indicator}', 'discardRequestedChangesQuestionnaireCountryIndicator');
                Route::post('/submit_requested_changes', 'submitQuestionnaireCountryRequestedChanges');
            });
        });
    });

    Route::controller(MyAccountController::class)->prefix('my-account')->group(function () {
        Route::get('/', 'view');
        Route::middleware('XssSanitizer')->group(function () {
            Route::post('/update', 'update');
        });
    });

    Route::get('/contacts', function () {
        return view('components.contacts');
    });

    Route::get('/about-eu-csi', function () {
        return view('components.about');
    });

    Route::controller(DocumentLibraryController::class)->prefix('documents-library')->group(function () {
        Route::get('/', 'view');
        Route::get('/download/{document}', 'downloadDocumentLibrary');
    });

    Route::get('/reports', function () {
        return view('components.reports');
    });

    Route::get('/logout', [LogoutController::class, 'logout']);

    Route::get('/', function (Request $request) {
        if (isset($request['redirect_path'])) {
            return redirect($request['redirect_path']);
        }

        return redirect('/index/access');
    })->middleware('operator.viewer.redirect');
});

require __DIR__ . '/auth.php';
