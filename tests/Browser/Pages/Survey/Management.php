<?php

namespace Tests\Browser\Pages\Survey;

use Tests\Browser\Components\Loader;
use Tests\Browser\Components\Button;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class Management extends Page
{
    const WAIT_FOR_SECONDS = 10;
    const SURVEYS_TABLE = '@surveys_table';
    const SURVEY_SUBMITTED_BY = '@survey_submitted_by_';
    const SURVEY_FILL_IN_ONLINE = '@survey_fill_in_online_';
    const SURVEY_FILL_IN_OFFLINE = '@survey_fill_in_offline_';
    const SURVEY_VIEW = '@survey_view_';
    const SURVEY_VIEW_DASHBOARD = '@survey_view_dashboard_';
    const SURVEY_DOWNLOAD = '@survey_download_pdf_';
    const SURVEY_FILL_IN_OFFLINE_MODAL = '@survey_fill_in_offline_modal';
    const SURVEY_IMPORT_TEMPLATE_CONFLICT_MODAL = '@survey_import_template_conflict_modal';
    const SURVEY_IMPORT_ALERT_MESSAGE = '@survey_import_alert_message';
    const SURVEY_IMPORT_ALERT_INDICATORS = '@survey_import_alert_indicators';
    const SURVEY_DOWNLOAD_TEMPLATE = '@survey_download_template';
    const SURVEY_IMPORT_TEMPLATE = '@survey_import_template';
    const SURVEY_IMPORT_CONTINUE = '@survey_import_continue';

    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/questionnaire/management';
    }

    /**
     * Assert the surveys management page.
     */
    public function assert(Browser $browser): void
    {
        $browser->on(new Loader(self::WAIT_FOR_SECONDS));
        $this->assertPath($browser);
        $browser->assertSeeIn('@surveys_title', 'List of Surveys');
    }

    public function assertPath(Browser $browser): void
    {
        $browser->waitForLocation($this->url(), self::WAIT_FOR_SECONDS);
    }

    /**
     * Assert the surveys management datatable.
     */
    public function assertDataTable(Browser $browser, $questionnaire_country_id, $status, $submitted_by = null): void
    {
        $browser->whenAvailable(self::SURVEYS_TABLE, function ($table) use ($questionnaire_country_id, $status, $submitted_by) {
            $role = $this->user->permissions->first()->role_id;
            $table->assertSeeIn('@survey_status_' . $questionnaire_country_id, $status);
            if ($status == 'Pending')
            {
                if (in_array($role, [2, 5])) {
                    $table->assertSeeNothingIn(self::SURVEY_SUBMITTED_BY . $questionnaire_country_id)
                          ->assertEnabled(self::SURVEY_VIEW_DASHBOARD . $questionnaire_country_id)
                          ->assertEnabled(self::SURVEY_DOWNLOAD . $questionnaire_country_id);
                }
                $table->assertEnabled(self::SURVEY_FILL_IN_ONLINE . $questionnaire_country_id)
                      ->assertEnabled(self::SURVEY_FILL_IN_OFFLINE . $questionnaire_country_id)
                      ->assertDisabled(self::SURVEY_VIEW . $questionnaire_country_id);
            }
            else
            {
                if (in_array($role, [2, 5]))
                {
                    if (is_null($submitted_by)) {
                        $table->assertSeeNothingIn(self::SURVEY_SUBMITTED_BY . $questionnaire_country_id)
                              ->assertDisabled(self::SURVEY_VIEW . $questionnaire_country_id);
                    }
                    else {
                        $table->assertSeeIn(self::SURVEY_SUBMITTED_BY . $questionnaire_country_id, $submitted_by)
                              ->assertEnabled(self::SURVEY_VIEW . $questionnaire_country_id);
                    }
                    $table->assertEnabled(self::SURVEY_VIEW_DASHBOARD . $questionnaire_country_id)
                          ->assertEnabled(self::SURVEY_DOWNLOAD . $questionnaire_country_id);
                }
                else {
                    $table->assertEnabled(self::SURVEY_VIEW . $questionnaire_country_id);
                }
                $table->assertDisabled(self::SURVEY_FILL_IN_ONLINE . $questionnaire_country_id)
                      ->assertDisabled(self::SURVEY_FILL_IN_OFFLINE . $questionnaire_country_id);
            }

            if ($role == 3) {
                $table->assertMissing(self::SURVEY_VIEW_DASHBOARD . $questionnaire_country_id)
                      ->assertMissing(self::SURVEY_DOWNLOAD . $questionnaire_country_id);
            }
        });
    }

    public function assertFillInOfflineModal(Browser $browser): void
    {
        $browser->whenAvailable(self::SURVEY_FILL_IN_OFFLINE_MODAL, function ($modal) {
            $modal->assertSee('Fill In Survey Offline')
                  ->assertSee('You can download the following template in order to fill your survey offline, and afterwards you import your filled in data.')
                  ->assertSeeIn(self::SURVEY_DOWNLOAD_TEMPLATE, 'Download Template')
                  ->assertEnabled(self::SURVEY_DOWNLOAD_TEMPLATE)
                  ->assertSeeIn(self::SURVEY_IMPORT_TEMPLATE, 'Import Completed Survey')
                  ->assertEnabled(self::SURVEY_IMPORT_TEMPLATE);
        });
    }

    public function assertImportTemplateConflictModal(Browser $browser, $alert, $idicators): void
    {
        $browser->whenAvailable(self::SURVEY_IMPORT_TEMPLATE_CONFLICT_MODAL, function ($modal) use ($alert, $idicators) {
            $modal->assertSee('Indicator Conflict')
                  ->assertSeeIn(self::SURVEY_IMPORT_ALERT_MESSAGE, $alert)
                  ->assertSeeIn(self::SURVEY_IMPORT_ALERT_INDICATORS, implode("\n", $idicators));
        });
    }

    public function clickFillInOnline(Browser $browser, $id): void
    {
        $browser->within(self::SURVEYS_TABLE, function ($table) use ($id) {
            $table->on(new Button('@survey_fill_in_online_link_' . $id))
                  ->scrollAndClickButton();
        });
    }

    public function clickFillInOffline(Browser $browser, $id): void
    {
        $browser->within(self::SURVEYS_TABLE, function ($table) use ($id) {
            $table->on(new Button('@survey_fill_in_offline_link_' . $id))
                  ->scrollAndClickButton();
        });
    }

    public function clickDashboard(Browser $browser, $id): void
    {
        $browser->within(self::SURVEYS_TABLE, function ($table) use ($id) {
            $table->on(new Button(self::SURVEY_VIEW_DASHBOARD . $id))
                  ->scrollAndClickButton();
        });
    }

    public function clickImportTemplate(Browser $browser, $filename): void
    {
        $browser->whenAvailable(self::SURVEY_FILL_IN_OFFLINE_MODAL, function ($modal) use ($filename) {
            $modal->waitFor(self::SURVEY_IMPORT_TEMPLATE)
                  ->assertInputPresent('file')
                  ->attach('file', $filename);
        });
        $browser->on(new Loader(90))
                ->waitUntilMissing(self::SURVEY_FILL_IN_OFFLINE_MODAL, self::WAIT_FOR_SECONDS);
    }

    public function clickDownloadTemplate(Browser $browser): void
    {
        $browser->whenAvailable(self::SURVEY_FILL_IN_OFFLINE_MODAL, function ($modal) {
            $modal->on(new Button(self::SURVEY_DOWNLOAD_TEMPLATE))
                  ->scrollAndClickButton();
        });
        $browser->on(new Loader(90))
                ->within(self::SURVEY_FILL_IN_OFFLINE_MODAL, function ($modal) {
                    $modal->assertSeeIn(self::SURVEY_DOWNLOAD_TEMPLATE, 'Downloading Template')
                          ->assertDisabled(self::SURVEY_DOWNLOAD_TEMPLATE)
                          ->waitForTextIn(self::SURVEY_DOWNLOAD_TEMPLATE, 'Download Template', 90);
            });
    }

    public function clickContinue(Browser $browser): void
    {
        $browser->whenAvailable(self::SURVEY_IMPORT_TEMPLATE_CONFLICT_MODAL, function ($modal) {
            $modal->on(new Button(self::SURVEY_IMPORT_CONTINUE))
                  ->scrollAndClickButton();
        });
    }
}
