<?php

namespace Tests\Browser\Pages\Survey;

use App\HelperFunctions\GeneralHelper;
use Tests\Browser\Components\Loader;
use Tests\Browser\Components\Button;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class AdminDashboard extends Page
{
    const WAIT_FOR_SECONDS = 10;
    const DASHBOARD_SURVEY_LOADED = '@dashboard_survey_loaded';
    const DASHBOARD_INDICATOR_VALUES = '@dashboard_indicator_values';
    const DASHBOARD_DOWNLOAD_DATA = '@dashboard_download_data';
    const DASHBOARD_TABLE = '@dashboard_table';

    protected $id;
    protected $title;

    public function __construct($id, $title)
    {
        $this->id = $id;
        $this->title = $title;
    }

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/questionnaire/admin/dashboard/' . $this->id;
    }

    /**
     * Assert the survey admin dashboard page.
     */
    public function assert(Browser $browser): void
    {
        $browser->on(new Loader(self::WAIT_FOR_SECONDS));
        $this->assertPath($browser);
        $browser->assertSeeIn('@dashboard_survey_title', 'Survey Dashboard - ' . $this->title);
    }

    public function assertPath(Browser $browser): void
    {
        $browser->waitForLocation($this->url(), self::WAIT_FOR_SECONDS);
    }

    /**
     * Assert the survey admin dashboard actions.
     */
    public function assertActions(Browser $browser): void
    {
        $browser->within('@dashboard_survey_actions', function ($table) {
            $table->assertSelected(self::DASHBOARD_SURVEY_LOADED, $this->id)
                  ->assertSeeIn(self::DASHBOARD_INDICATOR_VALUES, 'Indicator Values')
                  ->assertEnabled(self::DASHBOARD_INDICATOR_VALUES)
                  ->assertSeeIn(self::DASHBOARD_DOWNLOAD_DATA, 'Download Data')
                  ->assertEnabled(self::DASHBOARD_DOWNLOAD_DATA);
        });
    }

    public function getDataTableData($questionnaire_countries): array
    {
        $datatable_data = [];

        foreach ($questionnaire_countries as $questionnaire_country)
        {
            if (!is_null($questionnaire_country->id)) {
                $datatable_data[$questionnaire_country->id] = [
                    'country_name' => $questionnaire_country->country_name,
                    'in_progress' => (is_null($questionnaire_country->submitted_by)) ? $questionnaire_country->percentage_in_progress . '%' : '-',
                    'approved' => $questionnaire_country->percentage_approved . '%',
                    'status' => $questionnaire_country->status,
                    'primary_poc' => $questionnaire_country->primary_poc,
                    'last_survey_submitted' => (!is_null($questionnaire_country->submitted_at)) ? GeneralHelper::dateFormat($questionnaire_country->submitted_at, 'd-m-Y') : '-',
                    'last_requested_changes_submitted' => (!is_null($questionnaire_country->requested_changes_submitted_at)) ? GeneralHelper::dateFormat($questionnaire_country->requested_changes_submitted_at, 'd-m-Y') : '-',
                    'last_requested_changes_deadline' => (!is_null($questionnaire_country->requested_changes_deadline)) ? GeneralHelper::dateFormat($questionnaire_country->requested_changes_deadline, 'd-m-Y') : '-'
                ];
            }
        }

        return $datatable_data;
    }

    /**
     * Assert the survey admin dashboard datatable.
     */
    public function assertDataTable(Browser $browser, $datatable_data): void
    {
        $browser->whenAvailable(self::DASHBOARD_TABLE, function ($table) use ($datatable_data) {
            $table->waitUntilMissingText('Loading...', self::WAIT_FOR_SECONDS);
            foreach ($datatable_data as $questionnaire_country_id => $questionnaire_country_data)
            {
                $table->assertScript("$(\"[dusk='" . substr('@datatable_country_name_' . $questionnaire_country_id, 1) . "']\").text();", $questionnaire_country_data['country_name'])
                      ->assertScript("$(\"[dusk='" . substr('@datatable_in_progress_' . $questionnaire_country_id, 1) . "']\").text();", $questionnaire_country_data['in_progress'])
                      ->assertScript("$(\"[dusk='" . substr('@datatable_approved_' . $questionnaire_country_id, 1) . "']\").text();", $questionnaire_country_data['approved'])
                      ->assertScript("$(\"[dusk='" . substr('@datatable_status_' . $questionnaire_country_id, 1) . "']\").text();", $questionnaire_country_data['status'])
                      ->assertScript("$(\"[dusk='" . substr('@datatable_primary_poc_' . $questionnaire_country_id, 1) . "']\").text();", $questionnaire_country_data['primary_poc'])
                      ->assertScript("$(\"[dusk='" . substr('@datatable_last_survey_submitted_' . $questionnaire_country_id, 1) . "']\").text();", $questionnaire_country_data['last_survey_submitted'])
                      ->assertScript("$(\"[dusk='" . substr('@datatable_last_requested_changes_submitted_' . $questionnaire_country_id, 1) . "']\").text();", $questionnaire_country_data['last_requested_changes_submitted'])
                      ->assertScript("$(\"[dusk='" . substr('@datatable_last_requested_changes_deadline_' . $questionnaire_country_id, 1) . "']\").text();", $questionnaire_country_data['last_requested_changes_deadline'])
                      ->assertEnabled('@datatable_review_survey_' . $questionnaire_country_id)
                      ->assertEnabled('@datatable_indicators_dashboard_' . $questionnaire_country_id)
                      ->assertEnabled('@datatable_survey_summary_data_' . $questionnaire_country_id)
                      ->assertEnabled('@datatable_download_survey_data_' . $questionnaire_country_id);
            }
        });
    }

    public function clickDashboard(Browser $browser, $id): void
    {
        $browser->within(self::DASHBOARD_TABLE, function ($table) use ($id) {
            $table->waitUntilMissingText('Loading...', self::WAIT_FOR_SECONDS);
            $table->on(new Button('@datatable_indicators_dashboard_' . $id))
                  ->scrollAndClickButton();
        });
    }
}
