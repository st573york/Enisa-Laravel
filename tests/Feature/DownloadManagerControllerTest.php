<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use App\HelperFunctions\QuestionnaireCountryHelper;
use App\HelperFunctions\QuestionnaireHelper;
use App\HelperFunctions\TaskHelper;
use App\HelperFunctions\TestHelper;
use App\HelperFunctions\UserPermissions;
use App\Models\IndexConfiguration;
use App\Models\Indicator;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DownloadManagerControllerTest extends TestCase
{
    use RefreshDatabase;

    const EXPORT_DATA_ROUTE = '/export/data/create/';
    const EXPORT_REPORT_DATA_ROUTE = '/export/reportdata/create/';
    const INDEX_PROPERTIES_ROUTE = '/export/properties/create/';
    const SURVEY_EXCEL_ROUTE = '/export/surveyexcel/create/';
    const DOWNLOAD_ROUTE = '/export/data/download?task=';
    const EXPORTSURVEYEXCELTASK = 'ExportSurveyExcel';

    public function export_survey_template($user, $questionnaire, $request = [])
    {
        $filename = 'QuestionnaireTemplate' . $questionnaire->year . '.xlsx';

        $response = $this->actingAs($user)->post(self::SURVEY_EXCEL_ROUTE . $questionnaire->id, $request);
        $this->assertFileExists(storage_path() . '/app/' . $filename);
        $response->assertStatus(200)->assertSeeText('ok');

        TaskHelper::updateOrCreateTask([
            'type' => self::EXPORTSURVEYEXCELTASK,
            'status_id' => 1,
            'user_id' => $user->id,
            'index_configuration_id' => $questionnaire->configuration->id,
            'payload' => [
                'filename' => $filename
            ]
        ]);

        $response = $this->actingAs($user)->get(self::DOWNLOAD_ROUTE . self::EXPORTSURVEYEXCELTASK);
        $response->assertStatus(404);
        $response->assertExactJson([
            'error' => 'File processing...'
        ]);

        TaskHelper::updateOrCreateTask([
            'type' => self::EXPORTSURVEYEXCELTASK,
            'status_id' => 2,
            'user_id' => $user->id,
            'index_configuration_id' => $questionnaire->configuration->id,
            'payload' => [
                'filename' => $filename
            ]
        ]);

        $response = $this->actingAs($user)->get(self::DOWNLOAD_ROUTE . self::EXPORTSURVEYEXCELTASK);
        $response->assertOk()->assertDownload($filename);
        // Work around for deleting the file after download - the deleteFileAfterSend() does not work when running tests
        ob_start();
        $response->sendContent();
        ob_end_clean();
    }

    public function test_unauthorized_export_index_data()
    {
        $user = EcasTestHelper::validateTestUser('operator');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->actingAs($user)->post(self::EXPORT_DATA_ROUTE . $published_index->id, [
            'countries' => 'all',
            'sources' => 'all',
            'requestLocation' => 'index'
        ]);
        $response->assertStatus(302);
    }

    public function test_authenticated_export_index_data()
    {
        // $this->markTestIncomplete('This test requires data in order to be completed.');
        
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $draft_index = IndexConfiguration::where('draft', true)->latest('year')->first();
        $filename = 'EUCSI-' . $user->id . '-' . $published_index->year . '-all-data.xlsx';

        $response = $this->actingAs($user)->post(self::EXPORT_DATA_ROUTE . $draft_index->id, [
            'countries' => 'all',
            'sources' => 'all',
            'requestLocation' => 'index'
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => 'Index is not published!'
        ]);

        $response = $this->actingAs($user)->post(self::EXPORT_DATA_ROUTE . $published_index->id, [
            'countries' => 'all',
            'sources' => 'all',
            'requestLocation' => 'index'
        ]);
        $this->assertFileExists(storage_path() . '/app/' . $filename);
        $response->assertStatus(200)->assertSeeText('ok');

        TaskHelper::updateOrCreateTask([
            'type' => 'ExportData',
            'status_id' => 2,
            'user_id' => $user->id,
            'index_configuration_id' => $published_index->id,
            'payload' => [
                'filename' => $filename
            ]
        ]);

        $response = $this->actingAs($user)->get(self::DOWNLOAD_ROUTE . 'ExportData');
        $response->assertOk()->assertDownload($filename);
        // Work around for deleting the file after download - the deleteFileAfterSend() does not work when running tests
        ob_start();
        $response->sendContent();
        ob_end_clean();
    }

    public function test_unauthorized_export_report_data()
    {
        $user = EcasTestHelper::validateTestUser('viewer');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->actingAs($user)->post(self::EXPORT_REPORT_DATA_ROUTE, [
            'year' => $published_index->year
        ]);
        $response->assertStatus(302);
    }

    public function test_authenticated_export_report_data_fake()
    {
        Queue::fake();

        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $year = $published_index->year;
        $country = null;

        $response = $this->actingAs($user)->post(self::EXPORT_REPORT_DATA_ROUTE, [
            'year' => $year
        ]);
        $response->assertOk();

        Queue::assertPushed(function (\App\Jobs\ExportReportData $job) use ($year, $user, $country) {
            $this->assertTrue($year == $job->year);
            $this->assertTrue($user->id == $job->user->id);
            $this->assertTrue($country == $job->country);

            return $job;
        });
    }

    public function test_authenticated_export_report_data()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $year = $published_index->year;
        $filename = 'EUCSI-EU-report-' . $year . '.xlsx';

        $response = $this->actingAs($user)->post(self::EXPORT_REPORT_DATA_ROUTE, [
            'year' => $year
        ]);
        $this->assertFileExists(storage_path() . '/app/' . $filename);
        $response->assertOk()->assertSeeText('ok');

        TaskHelper::updateOrCreateTask([
            'type' => 'ExportReportData',
            'status_id' => 2,
            'user_id' => $user->id,
            'payload' => [
                'filename' => $filename
            ]
        ]);

        $response = $this->actingAs($user)->get(self::DOWNLOAD_ROUTE . 'ExportReportData');
        $response->assertOk()->assertDownload($filename);
        // Work around for deleting the file after download - the deleteFileAfterSend() does not work when running tests
        ob_start();
        $response->sendContent();
        ob_end_clean();
    }

    public function test_authenticated_export_survey_indicators()
    {
        // $this->markTestIncomplete('This test requires data in order to be completed.');

        $user = EcasTestHelper::validateTestUser('poc');
        $questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $filename = 'EUCSI-' . $user->id . '-' . $questionnaire->year . '-survey-indicators-' . UserPermissions::getUserCountries('iso')[0] . '.xlsx';
        
        $response = $this->actingAs($user)->post(self::EXPORT_DATA_ROUTE . $questionnaire->id, [
            'countries' => '90',
            'sources' => 'survey',
            'requestLocation' => 'survey'
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => 'No countries found for this user!'
        ]);

        $response = $this->actingAs($user)->post(self::EXPORT_DATA_ROUTE . $questionnaire->id, [
            'countries' => UserPermissions::getUserCountries()[0],
            'sources' => 'survey',
            'requestLocation' => 'survey'
        ]);
        $this->assertFileExists(storage_path() . '/app/' . $filename);
        $response->assertOk()->assertSeeText('ok');

        TaskHelper::updateOrCreateTask([
            'type' => 'ExportData',
            'status_id' => 2,
            'user_id' => $user->id,
            'index_configuration_id' => $questionnaire->configuration->id,
            'payload' => [
                'filename' => $filename
            ]
        ]);

        $response = $this->actingAs($user)->get(self::DOWNLOAD_ROUTE . 'ExportData');
        $response->assertOk()->assertDownload($filename);
        // Work around for deleting the file after download - the deleteFileAfterSend() does not work when running tests
        ob_start();
        $response->sendContent();
        ob_end_clean();
    }

    public function test_authenticated_export_data_fail()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get(self::DOWNLOAD_ROUTE . 'ExportData');
        $response->assertStatus(410)->assertJson(['error' => 'File not found!']);
    }

    public function test_authenticated_export_data_file_error()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $filename = 'EUCSI-' . $user->id . '-' . $published_index->year . '-all-data.xlsx';
        TaskHelper::updateOrCreateTask([
            'type' => 'ExportData',
            'status_id' => 3,
            'user_id' => $user->id,
            'index_configuration_id' => 1,
            'payload' => [
                'filename' => $filename
            ]
        ]);
        $response = $this->actingAs($user)->get(self::DOWNLOAD_ROUTE . 'ExportData');
        $response->assertStatus(400)->assertJson(['error' => 'File generation error!']);
    }

    public function test_authenticated_export_index_properties_fake()
    {
        Queue::fake();

        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $year = $published_index->year;

        $response = $this->actingAs($user)->post(self::INDEX_PROPERTIES_ROUTE . $year);
        $response->assertOk();

        Queue::assertPushed(function (\App\Jobs\ExportIndexProperties $job) use ($year, $user) {
            $this->assertTrue($year == $job->year);
            $this->assertTrue($user->id == $job->user->id);

            return $job;
        });
    }

    public function test_authenticated_export_index_properties()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $filename = 'Index_Properties_' . $published_index->year . '.xlsx';

        $response = $this->actingAs($user)->post(self::INDEX_PROPERTIES_ROUTE . $published_index->year);
        $this->assertFileExists(storage_path() . '/app/' . $filename);
        $response->assertStatus(200)->assertSeeText('ok');

        TaskHelper::updateOrCreateTask([
            'type' => 'ExportIndexProperties',
            'status_id' => 2,
            'user_id' => $user->id,
            'index_configuration_id' => $published_index->id,
            'payload' => [
                'filename' => $filename
            ]
        ]);

        $response = $this->actingAs($user)->get(self::DOWNLOAD_ROUTE . 'ExportIndexProperties');
        $response->assertOk()->assertDownload($filename);
        // Work around for deleting the file after download - the deleteFileAfterSend() does not work when running tests
        ob_start();
        $response->sendContent();
        ob_end_clean();
    }

    public function test_authenticated_export_survey_template_fake()
    {
        Queue::fake();

        $user = EcasTestHelper::validateTestUser('admin');
        $questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $indicators = $data['indicators_assigned_exact']['identifier'] ?? [];
        $country = null;
        $type = 'survey_template';

        $response = $this->actingAs($user)->post(self::SURVEY_EXCEL_ROUTE . $questionnaire->id, [
            'type' => 'survey_template'
        ]);
        $response->assertOk();

        Queue::assertPushed(function (\App\Jobs\ExportSurveyExcel $job) use ($user, $questionnaire, $indicators, $country, $type) {
            $this->assertTrue($user->id == $job->user->id);
            $this->assertTrue($questionnaire->id == $job->questionnaire->id);
            $this->assertEquals($indicators, $job->indicators);
            $this->assertTrue($country == $job->country);
            $this->assertTrue($type == $job->type);

            return $job;
        });
    }

    public function test_authenticated_export_survey_template()
    {
        $questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $questionnaire->id)->first();
        
        QuestionnaireHelper::createQuestionnaireTemplate($questionnaire);

        $admin = EcasTestHelper::validateTestUser('admin');
        $this->export_survey_template($admin, $questionnaire, ['type' => 'survey_template']);

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        // Operator
        $operator = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);
        $indicator = Indicator::where('category', 'survey')->where('year', $questionnaire->year)->first();

        // Assign indicator to Operator
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator->id, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire_country->id,
            'assignee' => $operator->id,
            'deadline' => date('Y-m-d'),
            'requested_changes' => false
        ]);
        $response->assertOk();

        $request = [
            'type' => 'survey_template',
            'questionnaire_country_id' => $questionnaire_country->id
        ];

        $this->export_survey_template($ppoc, $questionnaire, $request);
        $this->export_survey_template($operator, $questionnaire, $request);
    }
}
