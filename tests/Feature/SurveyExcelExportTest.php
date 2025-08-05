<?php

namespace Tests\Feature;

use App\Models\IndexConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyExcelExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_command()
    {
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();

        $this->artisan('export:survey-excel', ['-y' => $published_index->year])
             ->expectsOutput('Survey excel successfully exported!')
             ->assertExitCode(0);
    }
}
