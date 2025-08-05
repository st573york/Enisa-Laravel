<?php

namespace Tests\Browser\Pages\Survey;

use Tests\Browser\Components\Loader;
use Tests\Browser\Components\Button;
use Tests\Browser\Components\Checkbox;
use Tests\Browser\Components\Dropdown;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class DashboardManagement extends Page
{
    const WAIT_FOR_SECONDS = 10;
    const DASHBOARD_TABLE = '@dashboard_table';
    const DASHBOARD_APPROVE_SELECTED_INDICATORS = '@dashboard_approve_selected_indicators';
    const DASHBOARD_EDIT_SELECTED_INDICATORS = '@dashboard_edit_selected_indicators';
    const DASHBOARD_SUBMIT_REQUESTED_CHANGES = '@dashboard_submit_requested_changes';
    const DASHBOARD_REVIEW_SURVEY = '@dashboard_review_survey';
    const DASHBOARD_MODAL = '@dashboard_modal';
    const DASHBOARD_MODAL_TITLE = '@dashboard_modal_title';
    const DASHBOARD_EDIT_MODAL_DATA = '@dashboard_edit_modal_data';
    const DASHBOARD_EDIT_MODAL_ASSIGNEE = '@dashboard_edit_modal_assignee';
    const DASHBOARD_EDIT_MODAL_DEADLINE = '@dashboard_edit_modal_deadline';
    const DASHBOARD_MODAL_CLOSE = '@dashboard_modal_close';
    const DASHBOARD_MODAL_SAVE = '@dashboard_modal_save';

    protected $id;
    protected $title;
    protected $user;

    public function __construct($id, $title, $user)
    {
        $this->id = $id;
        $this->title = $title;
        $this->user = $user;
    }

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/questionnaire/dashboard/management/' . $this->id;
    }

    /**
     * Assert the survey dashboard management page.
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
     * Assert the survey dashboard management datatable.
     */
    public function assertDataTable(Browser $browser, $dashboard_data, $datatable_data): void
    {
        $browser->assertSeeIn('@dashboard_indicators_progress', $dashboard_data['progress'] . '%')
                ->whenAvailable(self::DASHBOARD_TABLE, function ($table) use ($datatable_data) {
                    $table->waitUntilMissingText('Loading...', self::WAIT_FOR_SECONDS);
                    foreach ($datatable_data as $indicator_id => $indicator_data)
                    {
                        if ($indicator_data['select'] == 'enabled') {
                            $table->assertEnabled('@datatable_indicator_select_' . $indicator_id);
                        }
                        else {
                            $table->assertDisabled('@datatable_indicator_select_' . $indicator_id);
                        }
                        $table->assertScript("$(\"[dusk='" . substr('@datatable_indicator_name_' . $indicator_id, 1) . "']\").text();", $indicator_data['name'])
                              ->assertScript("$(\"[dusk='" . substr('@datatable_indicator_assignee_' . $indicator_id, 1) . "']\").text();", $indicator_data['assignee'] . (($this->user->name == $indicator_data['assignee']) ? ' ' . View::YOU : ''))
                              ->assertScript("$(\"[dusk='" . substr('@datatable_indicator_status_' . $indicator_id, 1) . "']\").text();", $indicator_data['status'])
                              ->assertScript("$(\"[dusk='" . substr('@datatable_indicator_deadline_' . $indicator_id, 1) . "']\").text();", $indicator_data['deadline']);
                        if ($this->user->permissions->first()->role_id == 1)
                        {
                            if ($indicator_data['approve'] == 'enabled') {
                                $table->assertEnabled('@datatable_indicator_approve_' . $indicator_id);
                            }
                            else {
                                $table->assertDisabled('@datatable_indicator_approve_' . $indicator_id);
                            }
                        }
                        else
                        {
                            if ($indicator_data['edit'] == 'enabled') {
                                $table->assertEnabled('@datatable_indicator_edit_' . $indicator_id);
                            }
                            else {
                                $table->assertDisabled('@datatable_indicator_edit_' . $indicator_id);
                            }
                        }
                        if ($indicator_data['review'] == 'enabled') {
                            $table->assertEnabled('@datatable_indicator_review_' . $indicator_id);
                        }
                        else {
                            $table->assertDisabled('@datatable_indicator_review_' . $indicator_id);
                        }
                    }
                });
                if ($this->user->permissions->first()->role_id == 1)
                {
                    $browser->assertSeeIn(self::DASHBOARD_APPROVE_SELECTED_INDICATORS, 'Approve Selected Indicators');
                    if ($dashboard_data['approve_selected_indicators'] == 'enabled') {
                        $browser->assertEnabled(self::DASHBOARD_APPROVE_SELECTED_INDICATORS);
                    }
                    else {
                        $browser->assertDisabled(self::DASHBOARD_APPROVE_SELECTED_INDICATORS);
                    }
                }
                else
                {
                    $browser->assertSeeIn(self::DASHBOARD_EDIT_SELECTED_INDICATORS, 'Edit Selected Indicators');
                    if ($dashboard_data['edit_selected_indicators'] == 'enabled') {
                        $browser->assertEnabled(self::DASHBOARD_EDIT_SELECTED_INDICATORS);
                    }
                    else {
                        $browser->assertDisabled(self::DASHBOARD_EDIT_SELECTED_INDICATORS);
                    }
                }
                $browser->assertSeeIn(self::DASHBOARD_SUBMIT_REQUESTED_CHANGES, 'Submit Requested Changes');
                if ($dashboard_data['submit_requested_changes'] == 'enabled') {
                    $browser->assertEnabled(self::DASHBOARD_SUBMIT_REQUESTED_CHANGES);
                }
                elseif ($dashboard_data['submit_requested_changes'] == 'disabled') {
                    $browser->assertDisabled(self::DASHBOARD_SUBMIT_REQUESTED_CHANGES);
                }
                $browser->assertSeeIn(self::DASHBOARD_REVIEW_SURVEY, 'Review Survey')
                        ->assertEnabled(self::DASHBOARD_REVIEW_SURVEY);
    }

    public function assertEditModal(Browser $browser, $data): void
    {
        $browser->whenAvailable(self::DASHBOARD_MODAL, function ($modal) use ($data) {
            if (isset($data['title'])) {
                $modal->assertSeeIn(self::DASHBOARD_MODAL_TITLE, $data['title']);
            }
            $modal->within(self::DASHBOARD_EDIT_MODAL_DATA, function ($modal) use ($data) {
                if (isset($data['assignee'])) {
                    $modal->assertSelected(self::DASHBOARD_EDIT_MODAL_ASSIGNEE, $data['assignee']);
                }
                if (isset($data['deadline'])) {
                    $modal->assertInputValue(self::DASHBOARD_EDIT_MODAL_DEADLINE, $data['deadline']);
                }
            });
            if (isset($data['actions'])) {
                $modal->assertSeeIn(self::DASHBOARD_MODAL_CLOSE, 'Close')
                      ->assertEnabled(self::DASHBOARD_MODAL_CLOSE)
                      ->assertSeeIn(self::DASHBOARD_MODAL_SAVE, 'Save changes')
                      ->assertEnabled(self::DASHBOARD_MODAL_SAVE);
            }
        });
    }

    public function selectIndicators(Browser $browser, $indicators): void
    {
        $browser->within(self::DASHBOARD_TABLE, function ($table) use ($indicators) {
            foreach ($indicators as $indicator) {
                $table->on(new Checkbox('@datatable_indicator_select_' . $indicator))
                      ->scrollAndClickCheckbox();
            }
        });
    }

    public function clickApproveIndicator(Browser $browser, $indicator): void
    {
        $browser->within(self::DASHBOARD_TABLE, function ($table) use ($indicator) {
            $table->on(new Button('@datatable_indicator_approve_' . $indicator))
                  ->scrollAndClickButton();
        });
        $browser->on(new Loader(self::WAIT_FOR_SECONDS));
    }

    public function clickEditIndicator(Browser $browser, $indicator): void
    {
        $browser->within(self::DASHBOARD_TABLE, function ($table) use ($indicator) {
            $table->on(new Button('@datatable_indicator_edit_' . $indicator))
                  ->scrollAndClickButton();
        });
        $browser->on(new Loader(self::WAIT_FOR_SECONDS));
    }

    public function clickReviewIndicator(Browser $browser, $indicator): void
    {
        $browser->within(self::DASHBOARD_TABLE, function ($table) use ($indicator) {
            $table->on(new Button('@datatable_indicator_review_' . $indicator))
                  ->scrollAndClickButton();
        });
        $browser->on(new Loader(self::WAIT_FOR_SECONDS));
    }

    public function clickEditSelectedIndicators(Browser $browser): void
    {
        $browser->on(new Button(self::DASHBOARD_EDIT_SELECTED_INDICATORS))
                ->scrollAndClickButton()
                ->on(new Loader(self::WAIT_FOR_SECONDS));
    }

    public function clickSubmitRequestedChanges(Browser $browser): void
    {
        $browser->on(new Button(self::DASHBOARD_SUBMIT_REQUESTED_CHANGES))
                ->scrollAndClickButton()
                ->on(new Loader(self::WAIT_FOR_SECONDS))
                ->waitUntilDisabled(self::DASHBOARD_SUBMIT_REQUESTED_CHANGES, self::WAIT_FOR_SECONDS);
    }

    public function clickReviewSurvey(Browser $browser): void
    {
        $browser->on(new Button(self::DASHBOARD_REVIEW_SURVEY))
                ->scrollAndClickButton()
                ->on(new Loader(self::WAIT_FOR_SECONDS));
    }

    public function editSingleOrMultipleIndicators(Browser $browser, $data): array
    {
        $ret = [
            'assignee' => null,
            'deadline' => null
        ];

        $browser->within(self::DASHBOARD_MODAL, function ($modal) use ($data, &$ret) {
            $modal->within(self::DASHBOARD_EDIT_MODAL_DATA, function ($modal) use ($data) {
                if (isset($data['assignee'])) {
                    $modal->on(new Dropdown(self::DASHBOARD_EDIT_MODAL_ASSIGNEE, $data['assignee']))
                          ->scrollAndSelectDropdown();
                }
                if (isset($data['deadline']))
                {
                    $modal->press(self::DASHBOARD_EDIT_MODAL_DEADLINE);
                    $modal->within('.datepicker-days', function ($datepicker) use ($data) {
                        $deadline = intval($data['deadline']) * 1000;
                        $datepicker->script("$(\"[data-date='" . $deadline . "']\").click();");
                    });
                }
            });

            $assignee = explode(' (', $modal->text('@dashboard_edit_modal_assignee_' . $data['assignee']));
            
            $ret['assignee'] = $assignee[0];
            $ret['deadline'] = $modal->inputValue(self::DASHBOARD_EDIT_MODAL_DEADLINE);

            $modal->on(new Button(self::DASHBOARD_MODAL_SAVE))
                  ->clickButton();
        });
        $browser->on(new Loader(30))
                ->waitUntilMissing(self::DASHBOARD_MODAL, self::WAIT_FOR_SECONDS);

        return $ret;
    }

    public function clickBreadcrumb(Browser $browser, $breadcrumb, $text): void
    {
        $browser->on(new Button($breadcrumb, $text))
                ->scrollAndClickButton('top');
    }
}
