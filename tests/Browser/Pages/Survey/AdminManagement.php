<?php

namespace Tests\Browser\Pages\Survey;

use App\HelperFunctions\GeneralHelper;
use Tests\Browser\Components\Loader;
use Tests\Browser\Components\Button;
use Tests\Browser\Components\Checkbox;
use Tests\Browser\Components\Dropdown;
use Tests\Browser\Components\InputField;
use Tests\Browser\Components\RadioButton;
use Tests\Browser\Components\TinyMCE;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class AdminManagement extends Page
{
    const WAIT_FOR_SECONDS = 10;
    const SURVEYS_TABLE = '@surveys_table';
    const CREATE_SURVEY = '@create_survey';
    const SURVEY_MANAGE_MODAL = '@survey_manage_modal';
    const SURVEY_MANAGE_MODAL_TITLE = '@survey_manage_modal_title';
    const SURVEY_MANAGE_MODAL_INDEX = '@survey_manage_modal_index';
    const SURVEY_MANAGE_MODAL_INDEX_INVALID = '@survey_manage_modal_index_invalid';
    const SURVEY_MANAGE_MODAL_NAME = '@survey_manage_modal_name';
    const SURVEY_MANAGE_MODAL_NAME_INVALID = '@survey_manage_modal_name_invalid';
    const SURVEY_MANAGE_MODAL_DATA = '@survey_manage_modal_data';
    const SURVEY_MANAGE_MODAL_DEADLINE = '@survey_manage_modal_deadline';
    const SURVEY_MANAGE_MODAL_DEADLINE_INVALID = '@survey_manage_modal_deadline_invalid';
    const SURVEY_MANAGE_MODAL_SCOPE = '@survey_manage_modal_scope';
    const SURVEY_DELETE_MODAL_TEXT = '@survey_delete_modal_text';
    const SURVEY_MANAGE_MODAL_CLOSE = '@survey_manage_modal_close';
    const SURVEY_MANAGE_MODAL_PROCESS = '@survey_manage_modal_process';
    const SURVEY_PUBLISH_MODAL_TEXT = '@survey_publish_modal_data_text';
    const SURVEY_PUBLISH_MODAL_NOTIFY_ALL_INPUT = '@survey_publish_modal_notify_all_input';
    const SURVEY_PUBLISH_MODAL_NOTIFY_ALL_LABEL = '@survey_publish_modal_notify_all_label';
    const SURVEY_PUBLISH_MODAL_SELECT_USERS_INPUT = '@survey_publish_modal_select_users_input';
    const SURVEY_PUBLISH_MODAL_SELECT_USERS_LABEL = '@survey_publish_modal_select_users_label';
    const USERS_TABLE = "@users_table";

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/questionnaire/admin/management';
    }

    /**
     * Assert the surveys admin management page.
     */
    public function assert(Browser $browser): void
    {
        $browser->on(new Loader(self::WAIT_FOR_SECONDS));
        $this->assertPath($browser);
        $browser->assertSeeIn('@surveys_title', 'Manage Surveys');
    }

    public function assertPath(Browser $browser): void
    {
        $browser->waitForLocation($this->url(), self::WAIT_FOR_SECONDS);
    }

    public function getDataTableData($surveys): array
    {
        $datatable_data = [];

        foreach ($surveys as $survey) {
            $datatable_data[$survey->id] = [
                'title' => $survey->title,
                'year' => $survey->year,
                'deadline' => GeneralHelper::dateFormat($survey->deadline, 'd-m-Y'),
                'created_by' => $survey->created_by,
                'status' => ($survey->published) ? 'Published' : 'Draft',
                'submitted' => ($survey->not_submitted) ? false : true
            ];
        }

        return $datatable_data;
    }

    /**
     * Assert the surveys admin management datatable.
     */
    public function assertSurveysDataTable(Browser $browser, $surveys_data = []): void
    {
        $browser->whenAvailable(self::SURVEYS_TABLE, function ($table) use ($surveys_data) {
            $table->waitUntilMissingText('Loading...', self::WAIT_FOR_SECONDS);
            if (empty($surveys_data)) {
                $table->waitForText('No data available in table', self::WAIT_FOR_SECONDS);
            }
            else
            {
                foreach ($surveys_data as $survey_id => $survey_data)
                {
                    if (isset($survey_data['deleted']) &&
                        $survey_data['deleted'])
                    {
                        $table->assertMissing('@datatable_title_' . $survey_id);
                    }
                    else
                    {
                        $table->assertScript("$(\"[dusk='" . substr('@datatable_survey_title_' . $survey_id, 1) . "']\").text();", $survey_data['title'])
                              ->assertScript("$(\"[dusk='" . substr('@datatable_survey_year_' . $survey_id, 1) . "']\").text();", $survey_data['year'])
                              ->assertScript("$(\"[dusk='" . substr('@datatable_survey_deadline_' . $survey_id, 1) . "']\").text();", $survey_data['deadline'])
                              ->assertScript("$(\"[dusk='" . substr('@datatable_survey_created_by_' . $survey_id, 1) . "']\").text();", $survey_data['created_by'])
                              ->assertScript("$(\"[dusk='" . substr('@datatable_survey_status_' . $survey_id, 1) . "']\").text();", $survey_data['status'])
                              ->assertEnabled('@datatable_survey_edit_' . $survey_id)
                              ->assertEnabled('@datatable_survey_publish_' . $survey_id)
                              ->assertEnabled('@datatable_survey_download_template_' . $survey_id);
                        if ($survey_data['status'] == 'Draft') {
                            $table->assertDisabled('@datatable_survey_view_dashboard_' . $survey_id)
                                  ->assertEnabled('@datatable_survey_delete_' . $survey_id);
                        }
                        else {
                            $table->assertEnabled('@datatable_survey_view_dashboard_' . $survey_id)
                                  ->assertDisabled('@datatable_survey_delete_' . $survey_id);
                        }
                        if ($survey_data['submitted']) {
                            $table->assertDisabled('@datatable_survey_send_notifications_' . $survey_id);
                        }
                        else {
                            $table->assertEnabled('@datatable_survey_send_notifications_' . $survey_id);
                        }
                    }
                }
            }
        });
    }

    /**
     * Assert the users publish survey datatable.
     */
    public function assertUsersDataTable(Browser $browser, $users_data = []): void
    {
        $browser->whenAvailable(self::USERS_TABLE, function ($table) use ($users_data) {
            $table->waitUntilMissingText('Loading...', self::WAIT_FOR_SECONDS);
            if (empty($users_data)) {
                $table->waitForText('No data available in table', self::WAIT_FOR_SECONDS);
            }
            else
            {
                foreach ($users_data as $user_id => $user_data)
                {
                    if (isset($user_data['missing']) &&
                        $user_data['missing'])
                    {
                        $table->assertMissing('@datatable_user_select_' . $user_id);
                    }
                    else
                    {
                        if (isset($user_data['select']))
                        {
                            if ($user_data['select']) {
                                $table->assertChecked('@datatable_user_select_' . $user_id)
                                      ->assertDisabled('@datatable_user_select_' . $user_id);
                            }
                            else {
                                $table->assertNotChecked('@datatable_user_select_' . $user_id);
                            }
                        }
                        $table->assertScript("$(\"[dusk='" . substr('@datatable_user_name_' . $user_id, 1) . "']\").text();", $user_data['name'])
                              ->assertScript("$(\"[dusk='" . substr('@datatable_user_email_' . $user_id, 1) . "']\").text();", $user_data['email'])
                              ->assertScript("$(\"[dusk='" . substr('@datatable_user_role_' . $user_id, 1) . "']\").text();", $user_data['role'])
                              ->assertScript("$(\"[dusk='" . substr('@datatable_user_country_' . $user_id, 1) . "']\").text();", $user_data['country']);
                    }
                }
            }
        });
    }

    public function assertCreateOrEditModal(Browser $browser, $data): void
    {
        $browser->whenAvailable(self::SURVEY_MANAGE_MODAL, function ($modal) use ($data) {
            if (isset($data['title'])) {
                $modal->assertSeeIn(self::SURVEY_MANAGE_MODAL_TITLE, $data['title']);
            }
            if (isset($data['index']))
            {
                if (isset($data['index']['expected_indexes'])) {
                    $modal->assertSelectHasOptions(self::SURVEY_MANAGE_MODAL_INDEX, $data['index']['expected_indexes']);
                }
                if (isset($data['index']['error'])) {
                    $modal->waitForTextIn(self::SURVEY_MANAGE_MODAL_INDEX_INVALID, $data['index']['error']);
                }
                elseif (isset($data['index']['value'])) {
                    $modal->assertSelected(self::SURVEY_MANAGE_MODAL_INDEX, $data['index']['value']);
                }
                $modal->assertEnabled(self::SURVEY_MANAGE_MODAL_INDEX);
            }
            if (isset($data['name']))
            {
                if (isset($data['name']['error'])) {
                    $modal->waitForTextIn(self::SURVEY_MANAGE_MODAL_NAME_INVALID, $data['name']['error']);
                }
                elseif (isset($data['name']['value'])) {
                    $modal->assertInputValue(self::SURVEY_MANAGE_MODAL_NAME, $data['name']['value']);
                }
                $modal->assertEnabled(self::SURVEY_MANAGE_MODAL_NAME);
            }
            if (isset($data['deadline']))
            {
                if (isset($data['deadline']['error'])) {
                    $modal->waitForTextIn(self::SURVEY_MANAGE_MODAL_DEADLINE_INVALID, $data['deadline']['error']);
                }
                elseif (isset($data['deadline']['value'])) {
                    $modal->assertInputValue(self::SURVEY_MANAGE_MODAL_DEADLINE, $data['deadline']['value']);
                }
                $modal->assertEnabled(self::SURVEY_MANAGE_MODAL_DEADLINE);
            }
            if (isset($data['scope']))
            {
                if (isset($data['scope']['value'])) {
                    $modal->assertScript("tinymce.get('formScope').getContent({format: 'text'});", $data['scope']['value']);
                }
                $modal->assertEnabled(self::SURVEY_MANAGE_MODAL_SCOPE);
            }
            if (isset($data['actions']))
            {
                $modal->assertSeeIn(self::SURVEY_MANAGE_MODAL_CLOSE, 'Close')
                      ->assertEnabled(self::SURVEY_MANAGE_MODAL_CLOSE)
                      ->assertSeeIn(self::SURVEY_MANAGE_MODAL_PROCESS, 'Save changes')
                      ->assertEnabled(self::SURVEY_MANAGE_MODAL_PROCESS);
            }
        });
    }

    public function assertPublishModal(Browser $browser, $data): void
    {
        $browser->whenAvailable(self::SURVEY_MANAGE_MODAL, function ($modal) use ($data) {
            if (isset($data['text'])) {
                $modal->assertSeeIn(self::SURVEY_PUBLISH_MODAL_TEXT, $data['text']);
            }
            if (isset($data['options'])) {
                $modal->assertSeeIn(self::SURVEY_PUBLISH_MODAL_NOTIFY_ALL_LABEL, 'Notify all PPoCs')
                      ->assertChecked(self::SURVEY_PUBLISH_MODAL_NOTIFY_ALL_INPUT)
                      ->assertSeeIn(self::SURVEY_PUBLISH_MODAL_SELECT_USERS_LABEL, 'Select specific EUCSI users')
                      ->assertNotChecked(self::SURVEY_PUBLISH_MODAL_SELECT_USERS_INPUT);
            }
            if (isset($data['actions'])) {
                $modal->assertSeeIn(self::SURVEY_MANAGE_MODAL_CLOSE, 'Close')
                      ->assertEnabled(self::SURVEY_MANAGE_MODAL_CLOSE)
                      ->assertSeeIn(self::SURVEY_MANAGE_MODAL_PROCESS, 'Publish')
                      ->assertEnabled(self::SURVEY_MANAGE_MODAL_PROCESS);
            }
        });
    }

    public function assertDeleteModal(Browser $browser, $data): void
    {
        $browser->whenAvailable(self::SURVEY_MANAGE_MODAL, function ($modal) use ($data) {
            if (isset($data['title'])) {
                $modal->assertSeeIn(self::SURVEY_MANAGE_MODAL_TITLE, $data['title']);
            }
            if (isset($data['text'])) {
                $modal->assertSeeIn(self::SURVEY_DELETE_MODAL_TEXT, $data['text']);
            }
            if (isset($data['actions'])) {
                $modal->assertSeeIn(self::SURVEY_MANAGE_MODAL_CLOSE, 'Close')
                      ->assertEnabled(self::SURVEY_MANAGE_MODAL_CLOSE)
                      ->assertSeeIn(self::SURVEY_MANAGE_MODAL_PROCESS, 'Delete')
                      ->assertEnabled(self::SURVEY_MANAGE_MODAL_PROCESS);
            }
        });
    }

    public function createOrEditSurvey(Browser $browser, $data): void
    {
        $close = (isset($data['action']) && $data['action'] == 'close') ? true : false;
        $save = (isset($data['action']) && $data['action'] == 'save') ? true : false;

        $browser->within(self::SURVEY_MANAGE_MODAL, function ($modal) use ($data, $close, $save) {
            $modal->within(self::SURVEY_MANAGE_MODAL_DATA, function ($modal) use ($data) {
                if (isset($data['index'])) {
                    $modal->on(new Dropdown(self::SURVEY_MANAGE_MODAL_INDEX, $data['index']))
                          ->scrollAndSelectDropdown();
                }
                if (isset($data['name'])) {
                    $modal->on(new InputField(self::SURVEY_MANAGE_MODAL_NAME, $data['name']))
                          ->scrollAndTypeInputField();
                }
                if (isset($data['deadline']))
                {
                    $modal->press(self::SURVEY_MANAGE_MODAL_DEADLINE);
                    $modal->within('.datepicker-days', function ($datepicker) use ($data) {
                        $deadline = intval($data['deadline']) * 1000;
                        $datepicker->script("$(\"[data-date='" . $deadline . "']\").click();");
                    });
                }
                if (isset($data['scope'])) {
                    $modal->on(new TinyMCE('formScope', $data['scope']))->typeTinyMCE();
                }
            });
            if ($close) {
                $modal->on(new Button(self::SURVEY_MANAGE_MODAL_CLOSE))
                      ->clickButton();
            }
            elseif ($save) {
                $modal->on(new Button(self::SURVEY_MANAGE_MODAL_PROCESS))
                      ->clickButton();
            }
        });
        if ($save)
        {
            $browser->on(new Loader(30));

            $is_invalid = $browser->script("return $(\"[dusk='" . substr(self::SURVEY_MANAGE_MODAL, 1) . "']\").find('.invalid-feedback').is(':visible');");
            if (!$is_invalid[0]) {
                $browser->waitUntilMissing(self::SURVEY_MANAGE_MODAL, self::WAIT_FOR_SECONDS);
            }
        }
    }

    public function publishSurvey(Browser $browser, $data): void
    {
        $close = (isset($data['action']) && $data['action'] == 'close') ? true : false;
        $publish = (isset($data['action']) && $data['action'] == 'publish') ? true : false;

        $browser->within(self::SURVEY_MANAGE_MODAL, function ($modal) use ($data, $close, $publish) {
            $modal->within(self::SURVEY_MANAGE_MODAL_DATA, function ($modal) use ($data) {
                if (isset($data['option']))
                {
                    if ($data['option'] == 'notify_all') {
                        $modal->on(new RadioButton('#radio-all', 'Notify all PPoCs'))
                              ->scrollAndClickRadioButton();
                    }
                    elseif ($data['option'] == 'select_users') {
                        $modal->on(new RadioButton('#radio-specific', 'Select specific EUCSI users'))
                              ->scrollAndClickRadioButton();
                    }
                }
            });
            if ($close) {
                $modal->on(new Button(self::SURVEY_MANAGE_MODAL_CLOSE))
                      ->clickButton();
            }
            elseif ($publish) {
                $modal->on(new Button(self::SURVEY_MANAGE_MODAL_PROCESS))
                      ->clickButton();
            }
        });
        if ($publish)
        {
            $browser->on(new Loader(30));

            $is_invalid = $browser->script("return $(\"[dusk='" . substr(self::SURVEY_MANAGE_MODAL, 1) . "']\").find('.alert').is(':visible');");
            if (!$is_invalid[0]) {
                $browser->waitUntilMissing(self::SURVEY_MANAGE_MODAL, self::WAIT_FOR_SECONDS);
            }
        }
    }

    public function deleteSurvey(Browser $browser, $data): void
    {
        $delete = (isset($data['action']) && $data['action'] == 'delete') ? true : false;

        $browser->within(self::SURVEY_MANAGE_MODAL, function ($modal) use ($delete) {
            if ($delete) {
                $modal->on(new Button(self::SURVEY_MANAGE_MODAL_PROCESS))
                      ->clickButton();
            }
        });
        if ($delete) {
            $browser->on(new Loader(30))
                    ->waitUntilMissing(self::SURVEY_MANAGE_MODAL, self::WAIT_FOR_SECONDS);
        }
    }

    public function selectUsers(Browser $browser, $users): void
    {
        $browser->within(self::USERS_TABLE, function ($table) use ($users) {
            foreach ($users as $user) {
                $table->on(new Checkbox('@datatable_user_select_' . $user))
                      ->scrollAndClickCheckbox();
            }
        });
    }

    public function clickCreateSurvey(Browser $browser): void
    {
        $browser->on(new Button(self::CREATE_SURVEY))
                ->scrollAndClickButton('top')
                ->on(new Loader(self::WAIT_FOR_SECONDS));
    }

    public function clickEditSurvey(Browser $browser, $id): void
    {
        $browser->within(self::SURVEYS_TABLE, function ($table) use ($id) {
            $table->waitUntilMissingText('Loading...', self::WAIT_FOR_SECONDS)
                  ->on(new Button('@datatable_survey_edit_' . $id))
                  ->scrollAndClickButton();
        });
    }

    public function clickPublishSurvey(Browser $browser, $id): void
    {
        $browser->within(self::SURVEYS_TABLE, function ($table) use ($id) {
            $table->waitUntilMissingText('Loading...', self::WAIT_FOR_SECONDS)
                  ->on(new Button('@datatable_survey_publish_' . $id))
                  ->scrollAndClickButton();
        });
        $browser->on(new Loader(self::WAIT_FOR_SECONDS));
    }

    public function clickDashboard(Browser $browser, $id): void
    {
        $browser->within(self::SURVEYS_TABLE, function ($table) use ($id) {
            $table->waitUntilMissingText('Loading...', self::WAIT_FOR_SECONDS)
                  ->on(new Button('@datatable_survey_view_dashboard_' . $id))
                  ->scrollAndClickButton();
        });
    }

    public function clickDeleteSurvey(Browser $browser, $id): void
    {
        $browser->within(self::SURVEYS_TABLE, function ($table) use ($id) {
            $table->waitUntilMissingText('Loading...', self::WAIT_FOR_SECONDS)
                  ->on(new Button('@datatable_survey_delete_' . $id))
                  ->scrollAndClickButton();
        });
    }
}
