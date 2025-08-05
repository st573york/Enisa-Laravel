<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use App\HelperFunctions\TaskHelper;
use App\Models\IndexConfiguration;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use App\Models\EurostatIndicator;
use App\Models\EurostatIndicatorVariable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Carbon\Carbon;
use Tests\TestCase;

class IndexDataCollectionControllerTest extends TestCase
{
    use RefreshDatabase;

    const ERROR_NOT_AUTHORIZED = 'You are not authorized for this action!';
    const ERROR_NOT_ALLOWED = 'The requested action is not allowed!';
    const INDEXCALCULATIONTASK = 'IndexCalculation';
    const IMPORTDATACOLLECTIONTASK = 'ImportDataCollection';
    const EXTERNALDATACOLLECTIONTASK = 'ExternalDataCollection';

    protected $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->today = Carbon::now()->format('d-m-Y H:i:s');
    }

    public function createEurostatIndicators($data)
    {
        foreach ($data['eurostat_indicators'] as $eurostat_indicator) {
            EurostatIndicator::create([
                'name' => $eurostat_indicator->name,
                'source' => $eurostat_indicator->source,
                'identifier' => $eurostat_indicator->identifier,
                'country_id' => $eurostat_indicator->country_id,
                'report_year' => $eurostat_indicator->report_year,
                'value' => $eurostat_indicator->value
            ]);
        }
    }

    public function assertDataCollection($published_index, $status)
    {
        $response = Livewire::test(\App\Http\Livewire\DataCollection::class, ['index' => $published_index]);
        $response->assertOk()
                 ->assertViewIs('livewire.data-collection')
                 ->assertViewHas('published_indexes')
                 ->assertViewHas('loaded_index_data')
                 ->assertViewHas('latest_index_data')
                 ->assertViewHas('questionnaire')
                 ->assertViewHas('task')
                 ->assertSeeText($status)
                 ->assertEmitted('indexCalculation' . preg_replace('/[\s_]/', '', $status));
    }

    public function assertImportDataCollection($published_index, $status)
    {
        $response = Livewire::test(\App\Http\Livewire\ImportDataCollection::class, ['index' => $published_index]);
        $response->assertOk()
                 ->assertViewIs('livewire.import-data-collection')
                 ->assertViewHas('published_indexes')
                 ->assertViewHas('loaded_index_data')
                 ->assertViewHas('latest_index_data')
                 ->assertViewHas('task')
                 ->assertSeeText($status)
                 ->assertEmitted('importDataCollection' . preg_replace('/[\s_]/', '', $status));
    }

    public function assertExternalDataCollection($published_index, $status)
    {
        $response = Livewire::test(\App\Http\Livewire\ExternalDataCollection::class, ['index' => $published_index]);
        $response->assertOk()
                 ->assertViewIs('livewire.external-data-collection')
                 ->assertViewHas('published_indexes')
                 ->assertViewHas('loaded_index_data')
                 ->assertViewHas('latest_index_data')
                 ->assertViewHas('task')
                 ->assertSeeText($status)
                 ->assertEmitted('externalDataCollection' . preg_replace('/[\s_]/', '', $status));
    }

    public function assertIndicatorValues($published_index, $eurostat_indicators, $approved_index = null)
    {
        $indicators = Indicator::where('category', 'eurostat')->where('year', $published_index->year)->get()->keyBy('identifier')->toArray();

        foreach ($eurostat_indicators as $eurostat_indicator)
        {
            if (!is_null($approved_index) &&
                $approved_index->country_id == $eurostat_indicator['country_id'])
            {
                continue;
            }

            $this->assertTrue(
                IndicatorValue::where('indicator_id', $indicators[$eurostat_indicator['identifier']]['id'])
                    ->where('country_id', $eurostat_indicator['country_id'])
                    ->where('value', $eurostat_indicator['value'])
                    ->where('year', $indicators[$eurostat_indicator['identifier']]['year'])
                    ->exists()
            );
        }
    }

    public function test_unauthenticated_index_data_collection()
    {
        $_COOKIE['index-year'] = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->get('/index/datacollection');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_data_collection()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $_COOKIE['index-year'] = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->actingAs($user)->get('/index/datacollection');
        $response->assertOk()->assertViewIs('index.data-collection')->assertViewHasAll(['loaded_index_data', 'latest_index_data', 'task']);
    }

    public function test_unauthenticated_index_data_collection_list()
    {
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->get('/index/datacollection/list/' . $published_index->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_data_collection_list()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->actingAs($user)->get('/index/datacollection/list/' . $published_index->id);
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_unauthenticated_index_data_collection_calculate_index()
    {
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->post('/index/datacollection/calculate/' . $published_index->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_data_collection_calculate_index_fake()
    {
        Queue::fake();

        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->actingAs($user)->post('/index/datacollection/calculate/' . $published_index->id);
        $response->assertOk();

        Queue::assertPushed(function (\App\Jobs\IndexCalculation $job) use ($published_index, $user) {
            $this->assertTrue($published_index->id == $job->index->id);
            $this->assertTrue($user->id == $job->user->id);
            $this->assertDataCollection($published_index, 'No Calculation');

            return $job;
        });
    }

    public function test_authenticated_index_data_collection_calculate_index()
    {
        // $this->markTestIncomplete('This test requires data in order to be completed.');

        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::where('draft', false)->orderBy('year')->first();
        $latest_published_index = IndexConfiguration::getLatestPublishedConfiguration();

        $response = $this->actingAs($user)->post('/index/datacollection/calculate/' . $published_index->id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $response = $this->actingAs($user)->post('/index/datacollection/calculate/' . $latest_published_index->id);
        $response->assertOk();
    }

    public function test_unauthenticated_index_import_data_collection()
    {
        $_COOKIE['index-year'] = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->get('/index/datacollection/importdata');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_import_data_collection()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $_COOKIE['index-year'] = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->actingAs($user)->get('/index/datacollection/importdata');
        $response->assertOk()->assertViewIs('index.import-data-collection')->assertViewHasAll(['published_indexes', 'loaded_index_data', 'latest_index_data', 'task', 'indicators', 'countries', 'table_data']);
    }

    public function test_unauthenticated_index_import_data_collection_list()
    {
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->call('get', '/index/datacollection/importdata/list/' . $published_index->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_import_data_collection_list()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->actingAs($user)->call('get', '/index/datacollection/importdata/list/' . $published_index->id, []);
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_unauthenticated_index_import_data_collection_show()
    {
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->get('/index/datacollection/importdata/show/' . $published_index->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_import_data_collection_show()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::where('draft', false)->orderBy('year')->first();
        $latest_published_index = IndexConfiguration::getLatestPublishedConfiguration();

        $response = $this->actingAs($user)->get('/index/datacollection/importdata/show/' . $published_index->id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $task = TaskHelper::updateOrCreateTask([
            'type' => self::IMPORTDATACOLLECTIONTASK,
            'status_id' => 1,
            'index_configuration_id' => $latest_published_index->id,
            'payload' => [
                'last_import_data_collection_by' => $user->id,
                'last_import_data_collection_at' => $this->today
            ]
        ]);

        $this->assertImportDataCollection($latest_published_index, 'In Progress');

        $response = $this->actingAs($user)->get('/index/datacollection/importdata/show/' . $latest_published_index->id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        TaskHelper::deleteTask($task);

        $this->assertImportDataCollection($latest_published_index, 'No Import');

        $response = $this->actingAs($user)->get('/index/datacollection/importdata/show/' . $latest_published_index->id);
        $response->assertOk()->assertViewIs('ajax.index-data-collection-management');
    }

    /**
     * @group import_data_excel
     */
    public function test_unauthenticated_index_import_data_collection_store()
    {
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->post('/index/datacollection/importdata/store/' . $published_index->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    /**
     * @group import_data_excel
     */
    public function test_authenticated_required_index_import_data_collection_store()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->actingAs($user)->post('/index/datacollection/importdata/store/' . $published_index->id, [
            'file' => ''
        ]);
        $response->assertStatus(400);
        $response->assertJson([
            'file' => [
                'The file field is required.'
            ]
        ]);
    }

    /**
     * @group import_data_excel
     */
    public function test_authenticated_invalid_index_import_data_collection_store()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();

        $file = UploadedFile::fake()->create(
            'document.pdf',
            2512,
            'application/pdf'
        );

        $response = $this->actingAs($user)->post('/index/datacollection/importdata/store/' . $published_index->id, [
            'file' => $file
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'extension' => [
                'The file must be of type .xlsx'
            ],
            'file' => [
                'The file must be a file of type: xlsx.',
                'The file must not be greater than 2048 kilobytes.'
            ]
        ]);

        $file = UploadedFile::fake()->create(
            'document.xlsx',
            512,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $response = $this->actingAs($user)->post('/index/datacollection/importdata/store/' . $published_index->id, [
            'file' => $file
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => 'File could not be parsed correctly!'
        ]);
    }

    /**
     * @group import_data_excel
     */
    public function test_authenticated_index_import_data_collection_123_store_fake()
    {
        // $this->markTestIncomplete('This test requires data in order to be completed.');

        Queue::fake();

        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $filename = 'import-indicators.xlsx';
        $file = database_path() . '/seeders/Importers/import-files/' . $published_index->year . '/' . $filename;
        $test_file = storage_path() . '/app/test_files/' . $filename;

        File::copy($file, $test_file);

        $excel = new UploadedFile(
            $test_file,
            $filename,
            'application/xlsx',
            null,
            true
        );

        $response = $this->actingAs($user)->post('/index/datacollection/importdata/store/' . $published_index->id, [
            'file' => $excel
        ]);
        $response->assertOk();

        Queue::assertPushed(function (\App\Jobs\ImportDataCollection $job) use ($published_index, $user, $filename) {
            $parts = explode('_', $job->excel);

            $this->assertTrue($published_index->id == $job->index->id);
            $this->assertTrue($user->id == $job->user->id);
            $this->assertTrue($filename == $parts[1]);
            $this->assertImportDataCollection($published_index, 'No Import');

            // Delete file not deleted by Queue
            File::delete($job->excel);

            return $job;
        });
    }

    /**
     * @group import_data_excel
     */
    public function test_authenticated_index_import_data_collection_store()
    {
        // $this->markTestIncomplete('This test requires data in order to be completed.');

        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::where('draft', false)->orderBy('year')->first();
        $latest_published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $index = $latest_published_index->index()->first();
        $index->status_id = 3;
        $index->save();

        $response = $this->actingAs($user)->post('/index/datacollection/importdata/store/' . $published_index->id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $task = TaskHelper::updateOrCreateTask([
            'type' => self::IMPORTDATACOLLECTIONTASK,
            'status_id' => 1,
            'index_configuration_id' => $latest_published_index->id,
            'payload' => [
                'last_import_data_collection_by' => $user->id,
                'last_import_data_collection_at' => $this->today
            ]
        ]);

        $this->assertImportDataCollection($latest_published_index, 'In Progress');

        $response = $this->actingAs($user)->post('/index/datacollection/importdata/store/' . $latest_published_index->id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        TaskHelper::deleteTask($task);

        $this->assertImportDataCollection($latest_published_index, 'No Import');

        $filename = 'import-indicators.xlsx';
        $file = database_path() . '/seeders/Importers/import-files/' . $latest_published_index->year . '/' . $filename;
        $test_file = storage_path() . '/app/test_files/' . $filename;

        File::copy($file, $test_file);

        $excel = new UploadedFile(
            $test_file,
            $filename,
            'application/xlsx',
            null,
            true
        );

        $response = $this->actingAs($user)->post('/index/datacollection/importdata/store/' . $latest_published_index->id, [
            'file' => $excel
        ]);
        $response->assertOk();
    }

    public function test_unauthenticated_index_import_data_collection_discard()
    {
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->post('/index/datacollection/importdata/discard/' . $published_index->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_import_data_collection_discard()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::where('draft', false)->orderBy('year')->first();
        $latest_published_index = IndexConfiguration::getLatestPublishedConfiguration();

        $response = $this->actingAs($user)->post('/index/datacollection/importdata/discard/' . $published_index->id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        TaskHelper::updateOrCreateTask([
            'type' => self::IMPORTDATACOLLECTIONTASK,
            'status_id' => 1,
            'index_configuration_id' => $latest_published_index->id,
            'payload' => [
                'last_import_data_collection_by' => $user->id,
                'last_import_data_collection_at' => $this->today
            ]
        ]);

        $this->assertImportDataCollection($latest_published_index, 'In Progress');

        $response = $this->actingAs($user)->post('/index/datacollection/importdata/discard/' . $latest_published_index->id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        TaskHelper::updateOrCreateTask([
            'type' => self::IMPORTDATACOLLECTIONTASK,
            'status_id' => 2,
            'index_configuration_id' => $latest_published_index->id
        ]);

        $this->assertImportDataCollection($latest_published_index, 'Completed');

        $response = $this->actingAs($user)->post('/index/datacollection/importdata/discard/' . $latest_published_index->id);
        $response->assertOk();

        $this->assertImportDataCollection($latest_published_index, 'No Import');
    }

    public function test_unauthenticated_index_data_collection_approve_index()
    {
        // $this->markTestIncomplete('This test requires data in order to be completed.');

        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $index = $published_index->index()->first();
        $response = $this->post('/index/datacollection/approve/' . $published_index->id . '/' . $index->country_id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_data_collection_approve_index()
    {
        // $this->markTestIncomplete('This test requires data in order to be completed.');

        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $pending_index = $published_index->index()->first();
        $pending_index->status_id = 2;
        $pending_index->save();
        $approved_index = $published_index->index()->skip(1)->first();
        $approved_index->status_id = 3;
        $approved_index->save();

        TaskHelper::updateOrCreateTask([
            'type' => self::INDEXCALCULATIONTASK,
            'status_id' => 1,
            'index_configuration_id' => $published_index->id,
            'payload' => [
                'last_index_calculation_by' => $user->id,
                'last_index_calculation_at' => $this->today
            ]
        ]);

        $response = $this->actingAs($user)->post('/index/datacollection/approve/' . $published_index->id . '/' . $pending_index->country_id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        TaskHelper::updateOrCreateTask([
            'type' => self::INDEXCALCULATIONTASK,
            'status_id' => 2,
            'index_configuration_id' => $published_index->id
        ]);

        $response = $this->actingAs($user)->post('/index/datacollection/approve/' . $published_index->id . '/' . $approved_index->country_id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $response = $this->actingAs($user)->post('/index/datacollection/approve/' . $published_index->id . '/' . $pending_index->country_id);
        $response->assertOk();
    }

    public function test_unauthenticated_index_external_data_collection()
    {
        $_COOKIE['index-year'] = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->get('/index/datacollection/external');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_external_data_collection_status_check()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $_COOKIE['index-year'] = $published_index->year;

        $response = $this->actingAs($user)->get('/index/datacollection/external');
        $response->assertOk()->assertViewIs('index.external-data-collection')->assertViewHasAll(['loaded_index_data', 'task', 'indicators', 'countries', 'table_data'])
            ->assertSeeLivewire(\App\Http\Livewire\ExternalDataCollection::class);

        $this->assertExternalDataCollection($published_index, 'No Collection');

        TaskHelper::updateOrCreateTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'status_id' => 2,
            'index_configuration_id' => $published_index->id
        ]);

        $response = $this->actingAs($user)->get('/index/datacollection/external');
        $response->assertOk()->assertViewIs('index.external-data-collection')->assertViewHasAll(['loaded_index_data', 'task', 'indicators', 'countries', 'table_data'])
            ->assertSeeLivewire(\App\Http\Livewire\ExternalDataCollection::class);

        $this->assertExternalDataCollection($published_index, 'Completed');

        TaskHelper::updateOrCreateTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'status_id' => 4,
            'index_configuration_id' => $published_index->id
        ]);

        $response = $this->actingAs($user)->get('/index/datacollection/external');
        $response->assertOk()->assertViewIs('index.external-data-collection')->assertViewHasAll(['loaded_index_data', 'task', 'indicators', 'countries', 'table_data'])
            ->assertSeeLivewire(\App\Http\Livewire\ExternalDataCollection::class);

        $this->assertExternalDataCollection($published_index, 'Approved');
    }

    public function test_unauthenticated_index_external_data_collection_list()
    {
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->call('get', '/index/datacollection/external/list/' . $published_index->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_external_data_collection_list()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();

        $response = $this->actingAs($user)->call('get', '/index/datacollection/external/list/' . $published_index->id, []);
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        TaskHelper::updateOrCreateTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'status_id' => 2,
            'index_configuration_id' => $published_index->id
        ]);

        $response = $this->actingAs($user)->call('get', '/index/datacollection/external/list/' . $published_index->id, [
            'indicator' => 'All',
            'country' => 'All'
        ]);
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        TaskHelper::updateOrCreateTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'status_id' => 4,
            'index_configuration_id' => $published_index->id
        ]);

        $response = $this->actingAs($user)->call('get', '/index/datacollection/external/list/' . $published_index->id, [
            'indicator' => 'All',
            'country' => 'All'
        ]);
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_unauthenticated_index_external_data_collection_run()
    {
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->post('/index/datacollection/external/collect/' . $published_index->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_external_data_collection_run_fake()
    {
        Queue::fake();

        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->actingAs($user)->post('/index/datacollection/external/collect/' . $published_index->id);
        $response->assertOk();

        Queue::assertPushed(function (\App\Jobs\ExternalDataCollection $job) use ($published_index, $user) {
            $this->assertTrue($published_index->id == $job->index->id);
            $this->assertTrue($user->id == $job->user->id);
            $this->assertExternalDataCollection($published_index, 'No Collection');

            return $job;
        });
    }

    /**
     * @group external_data_collection_job
     */
    public function test_authenticated_index_external_data_collection_run()
    {
        $this->refreshApplication();

        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::where('draft', false)->orderBy('year')->first();
        $latest_published_index = IndexConfiguration::getLatestPublishedConfiguration();

        $response = $this->actingAs($user)->post('/index/datacollection/external/collect/' . $published_index->id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $job = new \App\Jobs\ExternalDataCollection($latest_published_index, $user);
        $retval = $job->handle();

        $this->assertEquals(0, $retval);
        $this->assertGreaterThan(0, EurostatIndicator::count());
        $this->assertGreaterThan(0, EurostatIndicatorVariable::count());
        $this->assertExternalDataCollection($latest_published_index, 'In Progress');

        TaskHelper::updateOrCreateTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'status_id' => 2,
            'index_configuration_id' => $latest_published_index->id
        ]);

        $this->assertExternalDataCollection($latest_published_index, 'Completed');

        return [
            'eurostat_indicators' => EurostatIndicator::all()
        ];
    }

    /**
     * @group external_data_collection_job
     * @depends test_authenticated_index_external_data_collection_run
     */
    public function test_authenticated_index_external_data_collection_approve_selected($data)
    {
        // $this->markTestIncomplete('This test requires data in order to be completed.');
        
        $this->refreshApplication();

        $this->createEurostatIndicators($data);

        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();

        TaskHelper::updateOrCreateTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'status_id' => 2,
            'index_configuration_id' => $published_index->id,
            'payload' => [
                'last_external_data_collection_by' => $user->id,
                'last_external_data_collection_at' => $this->today
            ]
        ]);

        $count = 1;
        $eurostat_indicators = [];
        foreach ($data['eurostat_indicators'] as $eurostat_indicator) {
            if ($count > 3) {
                break;
            }

            $eurostat_indicators[$eurostat_indicator->id] = [
                'identifier' => $eurostat_indicator->identifier,
                'country_id' => $eurostat_indicator->country_id,
                'value' => $eurostat_indicator->value
            ];

            $count++;
        }

        $response = $this->actingAs($user)->post('/index/datacollection/external/approve/' . $published_index->id, [
            'action' => 'approve-selected',
            'datatable-selected' => implode(', ', array_keys($eurostat_indicators))
        ]);
        $response->assertOk();

        $this->assertExternalDataCollection($published_index, 'Approved');

        $this->assertIndicatorValues($published_index, $eurostat_indicators);
    }

    /**
     * @group external_data_collection_job
     * @depends test_authenticated_index_external_data_collection_run
     */
    public function test_authenticated_index_external_data_collection_approve_all($data)
    {
        // $this->markTestIncomplete('This test requires data in order to be completed.');

        $this->refreshApplication();

        $this->createEurostatIndicators($data);

        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $approved_index = $published_index->index()->first();
        $approved_index->status_id = 3;
        $approved_index->save();

        TaskHelper::updateOrCreateTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'status_id' => 2,
            'index_configuration_id' => $published_index->id,
            'payload' => [
                'last_external_data_collection_by' => $user->id,
                'last_external_data_collection_at' => $this->today
            ]
        ]);

        $response = $this->actingAs($user)->post('/index/datacollection/external/approve/' . $published_index->id, [
            'action' => 'approve-all'
        ]);
        $response->assertOk();

        $this->assertExternalDataCollection($published_index, 'Approved');

        $this->assertIndicatorValues($published_index, $data['eurostat_indicators'], $approved_index);
    }

    public function test_authenticated_index_external_data_collection_approve()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::where('draft', false)->orderBy('year')->first();
        $latest_published_index = IndexConfiguration::getLatestPublishedConfiguration();

        $response = $this->actingAs($user)->post('/index/datacollection/external/approve/' . $published_index->id, []);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        TaskHelper::updateOrCreateTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'status_id' => 1,
            'index_configuration_id' => $latest_published_index->id,
            'payload' => [
                'last_external_data_collection_by' => $user->id,
                'last_external_data_collection_at' => $this->today
            ]
        ]);

        $response = $this->actingAs($user)->post('/index/datacollection/external/approve/' . $latest_published_index->id, []);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    public function test_authenticated_index_external_data_collection_discard()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::where('draft', false)->orderBy('year')->first();
        $latest_published_index = IndexConfiguration::getLatestPublishedConfiguration();

        $response = $this->actingAs($user)->post('/index/datacollection/external/discard/' . $published_index->id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        TaskHelper::updateOrCreateTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'status_id' => 1,
            'index_configuration_id' => $latest_published_index->id,
            'payload' => [
                'last_external_data_collection_by' => $user->id,
                'last_external_data_collection_at' => $this->today
            ]
        ]);

        $this->assertExternalDataCollection($latest_published_index, 'In Progress');

        $response = $this->actingAs($user)->post('/index/datacollection/external/discard/' . $latest_published_index->id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $task = TaskHelper::updateOrCreateTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'status_id' => 2,
            'index_configuration_id' => $latest_published_index->id,
            'payload' => [
                'last_external_data_approved_by' => $user->id,
                'last_external_data_approved_at' => $this->today
            ]
        ]);

        $response = $this->actingAs($user)->post('/index/datacollection/external/discard/' . $latest_published_index->id);
        $response->assertOk();

        $this->assertExternalDataCollection($latest_published_index, 'Approved');

        TaskHelper::deleteTask($task);

        TaskHelper::updateOrCreateTask([
            'type' => self::EXTERNALDATACOLLECTIONTASK,
            'status_id' => 2,
            'index_configuration_id' => $latest_published_index->id,
            'payload' => [
                'last_external_data_collection_by' => $user->id,
                'last_external_data_collection_at' => $this->today
            ]
        ]);

        $response = $this->actingAs($user)->post('/index/datacollection/external/discard/' . $latest_published_index->id);
        $response->assertOk();

        $this->assertExternalDataCollection($latest_published_index, 'No Collection');
    }
}
