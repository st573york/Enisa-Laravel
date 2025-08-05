<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use App\Models\IndexConfiguration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IndexPropertiesControllerTest extends TestCase
{
    use RefreshDatabase;

    const ERROR_NOT_ALLOWED = 'The requested action is not allowed!';
    const IMPORTINDEXPROPERTIESTASK = 'ImportIndexProperties';

    public function assertImportIndexProperties($status)
    {
        $response = Livewire::test(\App\Http\Livewire\IndexProperties::class);
        $response->assertOk()
            ->assertViewIs('livewire.index-properties')
            ->assertViewHas('years')
            ->assertViewHas('canImportProperties')
            ->assertViewHas('canPreviewSurvey')
            ->assertViewHas('canDownloadProperties')
            ->assertViewHas('task')
            ->assertSeeText($status)
            ->assertEmitted('importIndexProperties' . preg_replace('/[\s_]/', '', $status));
    }

    public function test_unauthenticated_index_properties()
    {
        $response = $this->get('/index/properties');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_properties()
    {
        $_COOKIE['index-year'] = date('Y');
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/index/properties');
        $response->assertOk()->assertViewIs('index.properties')->assertViewHasAll(['task']);
    }

    public function test_unauthenticated_index_properties_show()
    {
        $response = $this->get('/index/properties/show');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_properties_show()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $poc = EcasTestHelper::validateTestUser('poc');
        $_COOKIE['index-year'] = date('Y');

        $response = $this->actingAs($poc)->get('/index/properties/show');
        $response->assertRedirect('/access/denied/');

        $response = $this->actingAs($admin)->get('/index/properties/show');
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $published_index->draft = true;
        $published_index->save();

        $response = $this->actingAs($admin)->get('/index/properties/show');
        $response->assertOk()->assertViewIs('ajax.index-properties-management');
    }

    /**
     * @group import_properties_excel
     */
    public function test_unauthenticated_index_properties_store()
    {
        $response = $this->post('/index/properties/store', []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    /**
     * @group import_properties_excel
     */
    public function test_authenticated_required_index_properties_store()
    {
        $_COOKIE['index-year'] = date('Y');
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $published_index->draft = true;
        $published_index->save();
        $response = $this->actingAs($user)->post('/index/properties/store', [
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
     * @group import_properties_excel
     */
    public function test_authenticated_invalid_index_properties_store()
    {
        $_COOKIE['index-year'] = date('Y');
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $published_index->draft = true;
        $published_index->save();

        $file = UploadedFile::fake()->create(
            'document.pdf',
            2512,
            'application/pdf'
        );

        $response = $this->actingAs($user)->post('/index/properties/store', [
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

        $response = $this->actingAs($user)->post('/index/properties/store', [
            'file' => $file
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => 'File could not be parsed correctly!'
        ]);
    }

    /**
     * @group import_properties_excel
     */
    public function test_authenticated_index_properties_store_fake()
    {
        Queue::fake();

        $year = $_COOKIE['index-year'] = date('Y');
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $published_index->draft = true;
        $published_index->save();
        $filename = 'Index-Properties-With-Survey.xlsx';
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

        $response = $this->actingAs($user)->post('/index/properties/store', [
            'file' => $excel
        ]);
        $response->assertOk();

        Queue::assertPushed(function (\App\Jobs\ImportIndexProperties $job) use ($year, $user, $file, $filename) {
            $parts = explode('_', $job->excel);
            
            $this->assertTrue($year == $job->year);
            $this->assertTrue($user->id == $job->user->id);
            $this->assertTrue(basename($file) == $parts[1]);
            $this->assertTrue($filename == $job->originalName);
            $this->assertImportIndexProperties('No Import');

            // Delete file not deleted by Queue
            File::delete($job->excel);

            return $job;
        });
    }

    /**
     * @group import_properties_excel
     */
    public function test_authenticated_index_properties_store()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $poc = EcasTestHelper::validateTestUser('poc');
        $_COOKIE['index-year'] = date('Y');

        $response = $this->actingAs($poc)->post('/index/properties/store', []);
        $response->assertRedirect('/access/denied/');

        $response = $this->actingAs($admin)->post('/index/properties/store', []);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $published_index->draft = true;
        $published_index->save();

        $filename = 'Index-Properties-With-Survey.xlsx';
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

        $response = $this->actingAs($admin)->post('/index/properties/store', [
            'file' => $excel
        ]);
        $response->assertOk();
    }
}
