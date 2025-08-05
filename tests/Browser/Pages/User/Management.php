<?php

namespace Tests\Browser\Pages\User;

use App\HelperFunctions\GeneralHelper;
use Tests\Browser\Components\Loader;
use Tests\Browser\Components\Button;
use Tests\Browser\Components\Checkbox;
use Tests\Browser\Components\Dropdown;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class Management extends Page
{
    const WAIT_FOR_SECONDS = 10;
    const USERS_TABLE = '@users_table';
    const DELETE_SELECTED_USERS = '@delete_selected_users';
    const EDIT_SELECTED_USERS = '@edit_selected_users';
    const USER_MANAGE_MODAL = '@user_manage_modal';
    const USER_MANAGE_MODAL_TITLE = '@user_manage_modal_title';
    const USER_EDIT_MODAL_DATA = '@user_edit_modal_data';
    const USER_EDIT_MODAL_COUNTRY = '@user_edit_modal_country';
    const USER_EDIT_MODAL_ROLE = '@user_edit_modal_role';
    const USER_EDIT_MODAL_STATUS_SWITCH = '@user_edit_modal_status_switch';
    const USER_EDIT_MODAL_STATUS_TEXT = '@user_edit_modal_status_text';
    const USER_DELETE_MODAL_TEXT = '@user_delete_modal_text';
    const USER_MANAGE_MODAL_CLOSE = '@user_manage_modal_close';
    const USER_MANAGE_MODAL_PROCESS = '@user_manage_modal_process';

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
        return '/user/management';
    }

    /**
     * Assert the users management page.
     */
    public function assert(Browser $browser): void
    {
        $browser->on(new Loader(self::WAIT_FOR_SECONDS));
        $this->assertPath($browser);
        $browser->assertSeeIn('@users_title', 'Manage Users');
    }

    public function assertPath(Browser $browser): void
    {
        $browser->waitForLocation($this->url(), self::WAIT_FOR_SECONDS);
    }

    public function getDataTableData($users): array
    {
        $datatable_data = [];

        foreach ($users as $user) {
            $datatable_data[$user->id] = [
                'select' => ($this->user->id == $user->id) ? 'disabled' : 'enabled',
                'name' => $user->name,
                'email' => (!is_null($user->email)) ? $user->email : '-',
                'role' => (!is_null($user->role_name)) ? $user->role_name : '-',
                'country' => (!is_null($user->country)) ? $user->country : '-',
                'last_login' => (!is_null($user->last_login_at)) ? GeneralHelper::dateFormat($user->last_login_at, 'd-m-Y') : '-',
                'status_switch' => ($this->user->id == $user->id) ? 'disabled' : 'enabled',
                'status_text' => ($user->blocked) ? 'Blocked' : 'Enabled',
                'delete' => ($this->user->id == $user->id) ? 'disabled' : 'enabled',
            ];
        }

        return $datatable_data;
    }

    /**
     * Assert the users management datatable.
     */
    public function assertDataTable(Browser $browser, $datatable_data): void
    {
        $browser->whenAvailable(self::USERS_TABLE, function ($table) use ($datatable_data) {
            $table->waitUntilMissingText('Loading...', self::WAIT_FOR_SECONDS);
            foreach ($datatable_data as $user_id => $user_data)
            {
                if (isset($user_data['deleted']) &&
                    $user_data['deleted'])
                {
                    $table->assertMissing('@datatable_user_name_' . $user_id);
                }
                else
                {
                    if ($user_data['select'] == 'enabled') {
                        $table->assertEnabled('@datatable_user_select_' . $user_id);
                    }
                    else {
                        $table->assertDisabled('@datatable_user_select_' . $user_id);
                    }
                    $table->assertScript("$(\"[dusk='" . substr('@datatable_user_name_' . $user_id, 1) . "']\").text();", $user_data['name'])
                          ->assertScript("$(\"[dusk='" . substr('@datatable_user_email_' . $user_id, 1) . "']\").text();", $user_data['email'])
                          ->assertScript("$(\"[dusk='" . substr('@datatable_user_role_' . $user_id, 1) . "']\").text();", $user_data['role'])
                          ->assertScript("$(\"[dusk='" . substr('@datatable_user_country_' . $user_id, 1) . "']\").text();", $user_data['country'])
                          ->assertScript("$(\"[dusk='" . substr('@datatable_user_last_login_' . $user_id, 1) . "']\").text();", $user_data['last_login'])
                          ->assertScript("$(\"[dusk='" . substr('@datatable_user_status_text_' . $user_id, 1) . "']\").text();", $user_data['status_text'])
                          ->assertScript("$(\"[dusk='" . substr('@datatable_user_status_switch_' . $user_id, 1) . "']\").is(':checked');", ($user_data['status_text'] == 'Enabled') ? true : false)
                          ->assertScript("$(\"[dusk='" . substr('@datatable_user_status_switch_' . $user_id, 1) . "']\").hasClass('switch-deactivated');", ($user_data['status_switch'] == 'disabled') ? true : false)
                          ->assertEnabled('@datatable_user_edit_' . $user_id);
                    if ($this->user->permissions->first()->role_id == 1)
                    {
                        $table->assertVisible('@datatable_user_delete_' . $user_id);
                        if ($user_data['delete'] == 'enabled') {
                            $table->assertEnabled('@datatable_user_delete_' . $user_id);
                        }
                        else {
                            $table->assertDisabled('@datatable_user_delete_' . $user_id);
                        }
                    }
                    else {
                        $table->assertMissing('@datatable_user_delete_' . $user_id);
                    }
                }
            }
        });
        if ($this->user->permissions->first()->role_id == 1) {
            $browser->assertVisible(self::DELETE_SELECTED_USERS)
                    ->assertDisabled(self::DELETE_SELECTED_USERS);
        }
        else {
            $browser->assertMissing(self::DELETE_SELECTED_USERS);
        }
        $browser->assertSeeIn(self::EDIT_SELECTED_USERS, 'Edit Selected Users')
                ->assertDisabled(self::EDIT_SELECTED_USERS);
    }

    public function assertEditModal(Browser $browser, $data): void
    {
        $browser->whenAvailable(self::USER_MANAGE_MODAL, function ($modal) use ($data) {
            if (isset($data['title'])) {
                $modal->assertSeeIn(self::USER_MANAGE_MODAL_TITLE, $data['title']);
            }
            $modal->within(self::USER_EDIT_MODAL_DATA, function ($modal) use ($data) {
                if (isset($data['missing_countries'])) {
                    $modal->assertSelectMissingOptions(self::USER_EDIT_MODAL_COUNTRY, $data['missing_countries']);
                }
                if (isset($data['expected_countries'])) {
                    $modal->assertSelectHasOptions(self::USER_EDIT_MODAL_COUNTRY, $data['expected_countries']);
                }
                if (isset($data['missing_roles'])) {
                    $modal->assertSelectMissingOptions(self::USER_EDIT_MODAL_ROLE, $data['missing_roles']);
                }
                if (isset($data['expected_roles'])) {
                    $modal->assertSelectHasOptions(self::USER_EDIT_MODAL_ROLE, $data['expected_roles']);
                }
                if (isset($data['country'])) {
                    $modal->assertSelected(self::USER_EDIT_MODAL_COUNTRY, $data['country'])
                          ->assertEnabled(self::USER_EDIT_MODAL_COUNTRY);
                }
                if (isset($data['role'])) {
                    $modal->assertSelected(self::USER_EDIT_MODAL_ROLE, $data['role'])
                          ->assertEnabled(self::USER_EDIT_MODAL_ROLE);
                }
                if (isset($data['status_text'])) {
                    $modal->assertSeeIn(self::USER_EDIT_MODAL_STATUS_TEXT, $data['status_text'])
                          ->assertScript("$(\"[dusk='" . substr(self::USER_EDIT_MODAL_STATUS_SWITCH, 1) . "']\").is(':checked');", ($data['status_text'] == 'Blocked') ? true : false);
                }
                if (isset($data['status_switch'])) {
                    $modal->assertScript("$(\"[dusk='" . substr(self::USER_EDIT_MODAL_STATUS_SWITCH, 1) . "']\").hasClass('switch-deactivated');", ($data['status_switch'] == 'disabled') ? true : false);
                }
            });
            if (isset($data['actions'])) {
                $modal->assertSeeIn(self::USER_MANAGE_MODAL_CLOSE, 'Close')
                      ->assertEnabled(self::USER_MANAGE_MODAL_CLOSE)
                      ->assertSeeIn(self::USER_MANAGE_MODAL_PROCESS, 'Save changes')
                      ->assertEnabled(self::USER_MANAGE_MODAL_PROCESS);
            }
        });
    }

    public function assertDeleteModal(Browser $browser, $data): void
    {
        $browser->whenAvailable(self::USER_MANAGE_MODAL, function ($modal) use ($data) {
            if (isset($data['title'])) {
                $modal->assertSeeIn(self::USER_MANAGE_MODAL_TITLE, $data['title']);
            }
            if (isset($data['text'])) {
                $modal->assertSeeIn(self::USER_DELETE_MODAL_TEXT, $data['text']);
            }
            if (isset($data['actions'])) {
                $modal->assertSeeIn(self::USER_MANAGE_MODAL_CLOSE, 'Close')
                      ->assertEnabled(self::USER_MANAGE_MODAL_CLOSE)
                      ->assertSeeIn(self::USER_MANAGE_MODAL_PROCESS, 'Delete')
                      ->assertEnabled(self::USER_MANAGE_MODAL_PROCESS);
            }
        });
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

    public function clickDeleteSelectedUsers(Browser $browser): void
    {
        $browser->on(new Button(self::DELETE_SELECTED_USERS))
                ->scrollAndClickButton();
    }

    public function clickEditSelectedUsers(Browser $browser): void
    {
        $browser->on(new Button(self::EDIT_SELECTED_USERS))
                ->scrollAndClickButton()
                ->on(new Loader(self::WAIT_FOR_SECONDS));
    }

    public function clickEnableBlockUser(Browser $browser, $user): void
    {
        $browser->within(self::USERS_TABLE, function ($table) use ($user) {
            $table->on(new Button('@datatable_user_status_switch_' . $user))
                  ->scrollAndClickButton();
        });
        $browser->on(new Loader(self::WAIT_FOR_SECONDS));
    }

    public function clickEditUser(Browser $browser, $user): void
    {
        $browser->within(self::USERS_TABLE, function ($table) use ($user) {
            $table->on(new Button('@datatable_user_edit_' . $user))
                  ->scrollAndClickButton();
        });
        $browser->on(new Loader(self::WAIT_FOR_SECONDS));
    }

    public function clickDeleteUser(Browser $browser, $user): void
    {
        $browser->within(self::USERS_TABLE, function ($table) use ($user) {
            $table->on(new Button('@datatable_user_delete_' . $user))
                  ->scrollAndClickButton();
        });
    }

    public function editSingleOrMultipleUsers(Browser $browser, $data): void
    {
        $save = (isset($data['action']) && $data['action'] == 'save') ? true : false;

        $browser->within(self::USER_MANAGE_MODAL, function ($modal) use ($data, $save) {
            $modal->within(self::USER_EDIT_MODAL_DATA, function ($modal) use ($data) {
                if (isset($data['country'])) {
                    $modal->on(new Dropdown(self::USER_EDIT_MODAL_COUNTRY, $data['country']))
                          ->scrollAndSelectDropdown();
                }
                if (isset($data['role'])) {
                    $modal->on(new Dropdown(self::USER_EDIT_MODAL_ROLE, $data['role']))
                          ->scrollAndSelectDropdown();
                }
                if (in_array('enable_block', $data)) {
                    $modal->on(new Button(self::USER_EDIT_MODAL_STATUS_SWITCH))
                          ->clickButton();
                }
            });
            if ($save) {
                $modal->on(new Button(self::USER_MANAGE_MODAL_PROCESS))
                      ->clickButton();
            }
        });
        if ($save) {
            $browser->on(new Loader(30))
                    ->waitUntilMissing(self::USER_MANAGE_MODAL, self::WAIT_FOR_SECONDS);
        }
    }

    public function deleteSingleOrMultipleUsers(Browser $browser, $data): void
    {
        $delete = (isset($data['action']) && $data['action'] == 'delete') ? true : false;

        $browser->within(self::USER_MANAGE_MODAL, function ($modal) use ($delete) {
            if ($delete) {
                $modal->on(new Button(self::USER_MANAGE_MODAL_PROCESS))
                      ->clickButton();
            }
        });
        if ($delete) {
            $browser->on(new Loader(30))
                    ->waitUntilMissing(self::USER_MANAGE_MODAL, self::WAIT_FOR_SECONDS);
        }
    }
}
