<?php

namespace Tests\Browser\Pages\Invitation;

use Tests\Browser\Components\Loader;
use Tests\Browser\Components\Button;
use Tests\Browser\Components\Dropdown;
use Tests\Browser\Components\InputField;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class Management extends Page
{
    const WAIT_FOR_SECONDS = 10;
    const INVITATIONS_TABLE = '@invitations_table';
    const INVITE_NEW_USER = '@invite_new_user';
    const INVITATION_MANAGE_MODAL = '@invitation_manage_modal';
    const INVITATION_MANAGE_MODAL_TITLE = '@invitation_manage_modal_title';
    const INVITATION_MANAGE_MODAL_DATA = '@invitation_manage_modal_data';
    const INVITATION_MANAGE_MODAL_FIRSTNAME = '@invitation_manage_modal_firstname';
    const INVITATION_MANAGE_MODAL_FIRSTNAME_INVALID = '@invitation_manage_modal_firstname_invalid';
    const INVITATION_MANAGE_MODAL_LASTNAME = '@invitation_manage_modal_lastname';
    const INVITATION_MANAGE_MODAL_LASTNAME_INVALID = '@invitation_manage_modal_lastname_invalid';
    const INVITATION_MANAGE_MODAL_EMAIL = '@invitation_manage_modal_email';
    const INVITATION_MANAGE_MODAL_EMAIL_INVALID = '@invitation_manage_modal_email_invalid';
    const INVITATION_MANAGE_MODAL_COUNTRY = '@invitation_manage_modal_country';
    const INVITATION_MANAGE_MODAL_COUNTRY_INVALID = '@invitation_manage_modal_country_invalid';
    const INVITATION_MANAGE_MODAL_ROLE = '@invitation_manage_modal_role';
    const INVITATION_MANAGE_MODAL_ROLE_INVALID = '@invitation_manage_modal_role_invalid';
    const INVITATION_MANAGE_MODAL_CLOSE = '@invitation_manage_modal_close';
    const INVITATION_MANAGE_MODAL_PROCESS = '@invitation_manage_modal_process';

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/invitation/management';
    }

    /**
     * Assert the invitations management page.
     */
    public function assert(Browser $browser): void
    {
        $browser->on(new Loader(self::WAIT_FOR_SECONDS));
        $this->assertPath($browser);
        $browser->assertSeeIn('@invitations_title', 'Manage Invitations');
    }

    public function assertPath(Browser $browser): void
    {
        $browser->waitForLocation($this->url(), self::WAIT_FOR_SECONDS);
    }

    /**
     * Assert the invitations management datatable.
     */
    public function assertDataTable(Browser $browser, $datatable_data = []): void
    {
        $browser->assertSeeIn(self::INVITE_NEW_USER, 'Invite new user')
                ->assertEnabled(self::INVITE_NEW_USER)
                ->whenAvailable(self::INVITATIONS_TABLE, function ($table) use ($datatable_data) {
                    $table->waitUntilMissingText('Loading...', self::WAIT_FOR_SECONDS);
                    if (empty($datatable_data)) {
                        $table->waitForText('No data available in table', self::WAIT_FOR_SECONDS);
                    }
                    else
                    {
                        foreach ($datatable_data as $invitation_id => $invitation_data) {
                            $table->assertScript("$(\"[dusk='" . substr('@datatable_user_name_' . $invitation_id, 1) . "']\").text();", $invitation_data['name'])
                                  ->assertScript("$(\"[dusk='" . substr('@datatable_user_email_' . $invitation_id, 1) . "']\").text();", $invitation_data['email'])
                                  ->assertScript("$(\"[dusk='" . substr('@datatable_user_country_' . $invitation_id, 1) . "']\").text();", $invitation_data['country'])
                                  ->assertScript("$(\"[dusk='" . substr('@datatable_user_role_' . $invitation_id, 1) . "']\").text();", $invitation_data['role'])
                                  ->assertScript("$(\"[dusk='" . substr('@datatable_user_invited_by_' . $invitation_id, 1) . "']\").text();", $invitation_data['invited_by'])
                                  ->assertScript("$(\"[dusk='" . substr('@datatable_user_invited_at_' . $invitation_id, 1) . "']\").text();", $invitation_data['invited_at'])
                                  ->assertScript("$(\"[dusk='" . substr('@datatable_user_registered_at_' . $invitation_id, 1) . "']\").text();", $invitation_data['registered_at'])
                                  ->assertScript("$(\"[dusk='" . substr('@datatable_user_status_text_' . $invitation_id, 1) . "']\").text();", $invitation_data['status_text']);
                        }
                    }
                });
    }

    public function assertInviteModal(Browser $browser, $data): void
    {
        $browser->whenAvailable(self::INVITATION_MANAGE_MODAL, function ($modal) use ($data) {
            if (isset($data['title'])) {
                $modal->assertSeeIn(self::INVITATION_MANAGE_MODAL_TITLE, $data['title']);
            }
            if (isset($data['firstname']))
            {
                if (isset($data['firstname']['error'])) {
                    $modal->waitForTextIn(self::INVITATION_MANAGE_MODAL_FIRSTNAME_INVALID, $data['firstname']['error']);
                }
                elseif (isset($data['firstname']['value'])) {
                    $modal->assertInputValue(self::INVITATION_MANAGE_MODAL_FIRSTNAME, $data['firstname']['value']);
                }
                $modal->assertEnabled(self::INVITATION_MANAGE_MODAL_FIRSTNAME);
            }
            if (isset($data['lastname']))
            {
                if (isset($data['lastname']['error'])) {
                    $modal->waitForTextIn(self::INVITATION_MANAGE_MODAL_LASTNAME_INVALID, $data['lastname']['error']);
                }
                elseif (isset($data['lastname']['value'])) {
                    $modal->assertInputValue(self::INVITATION_MANAGE_MODAL_LASTNAME, $data['lastname']['value']);
                }
                $modal->assertEnabled(self::INVITATION_MANAGE_MODAL_LASTNAME);
            }
            if (isset($data['email']))
            {
                if (isset($data['email']['error'])) {
                    $modal->waitForTextIn(self::INVITATION_MANAGE_MODAL_EMAIL_INVALID, $data['email']['error']);
                }
                elseif (isset($data['email']['value'])) {
                    $modal->assertInputValue(self::INVITATION_MANAGE_MODAL_EMAIL, $data['email']['value']);
                }
                $modal->assertEnabled(self::INVITATION_MANAGE_MODAL_EMAIL);
            }
            if (isset($data['country']))
            {
                if (isset($data['country']['missing_countries'])) {
                    $modal->assertSelectMissingOptions(self::INVITATION_MANAGE_MODAL_COUNTRY, $data['country']['missing_countries']);
                }
                if (isset($data['country']['expected_countries'])) {
                    $modal->assertSelectHasOptions(self::INVITATION_MANAGE_MODAL_COUNTRY, $data['country']['expected_countries']);
                }
                if (isset($data['country']['error'])) {
                    $modal->waitForTextIn(self::INVITATION_MANAGE_MODAL_COUNTRY_INVALID, $data['country']['error']);
                }
                elseif (isset($data['country']['value'])) {
                    $modal->assertSelected(self::INVITATION_MANAGE_MODAL_COUNTRY, $data['country']['value']);
                }
                $modal->assertEnabled(self::INVITATION_MANAGE_MODAL_COUNTRY);
            }
            if (isset($data['role']))
            {
                if (isset($data['role']['missing_roles'])) {
                    $modal->assertSelectMissingOptions(self::INVITATION_MANAGE_MODAL_ROLE, $data['role']['missing_roles']);
                }
                if (isset($data['role']['expected_roles'])) {
                    $modal->assertSelectHasOptions(self::INVITATION_MANAGE_MODAL_ROLE, $data['role']['expected_roles']);
                }
                if (isset($data['role']['error'])) {
                    $modal->waitForTextIn(self::INVITATION_MANAGE_MODAL_ROLE_INVALID, $data['role']['error']);
                }
                elseif (isset($data['role']['value'])) {
                    $modal->assertSelected(self::INVITATION_MANAGE_MODAL_ROLE, $data['role']['value']);
                }
                $modal->assertEnabled(self::INVITATION_MANAGE_MODAL_ROLE);
            }
            if (isset($data['actions'])) {
                $modal->assertSeeIn(self::INVITATION_MANAGE_MODAL_CLOSE, 'Close')
                      ->assertEnabled(self::INVITATION_MANAGE_MODAL_CLOSE)
                      ->assertSeeIn(self::INVITATION_MANAGE_MODAL_PROCESS, 'Invite')
                      ->assertEnabled(self::INVITATION_MANAGE_MODAL_PROCESS);
            }
        });
    }

    public function clickInviteNewUser(Browser $browser): void
    {
        $browser->on(new Button(self::INVITE_NEW_USER))
                ->scrollAndClickButton('top')
                ->on(new Loader(self::WAIT_FOR_SECONDS));
    }

    public function inviteNewUser(Browser $browser, $data): void
    {
        $close = (isset($data['action']) && $data['action'] == 'close') ? true : false;
        $invite = (isset($data['action']) && $data['action'] == 'invite') ? true : false;

        $browser->within(self::INVITATION_MANAGE_MODAL, function ($modal) use ($data, $close, $invite) {
            $modal->within(self::INVITATION_MANAGE_MODAL_DATA, function ($modal) use ($data) {
                if (isset($data['firstname'])) {
                    $modal->on(new InputField(self::INVITATION_MANAGE_MODAL_FIRSTNAME, $data['firstname']))
                          ->scrollAndTypeInputField();
                }
                if (isset($data['lastname'])) {
                    $modal->on(new InputField(self::INVITATION_MANAGE_MODAL_LASTNAME, $data['lastname']))
                          ->scrollAndTypeInputField();
                }
                if (isset($data['email'])) {
                    $modal->on(new InputField(self::INVITATION_MANAGE_MODAL_EMAIL, $data['email']))
                          ->scrollAndTypeInputField();
                }
                if (isset($data['country'])) {
                    $modal->on(new Dropdown(self::INVITATION_MANAGE_MODAL_COUNTRY, $data['country']))
                          ->scrollAndSelectDropdown();
                }
                if (isset($data['role'])) {
                    $modal->on(new Dropdown(self::INVITATION_MANAGE_MODAL_ROLE, $data['role']))
                          ->scrollAndSelectDropdown();
                }
            });
            if ($close) {
                $modal->on(new Button(self::INVITATION_MANAGE_MODAL_CLOSE))
                      ->clickButton();
            }
            elseif ($invite) {
                $modal->on(new Button(self::INVITATION_MANAGE_MODAL_PROCESS))
                      ->clickButton();
            }
        });
        if ($invite)
        {
            $browser->on(new Loader(30));

            $is_invalid = $browser->script("return $(\"[dusk='" . substr(self::INVITATION_MANAGE_MODAL, 1) . "']\").find('.invalid-feedback').is(':visible');");
            if (!$is_invalid[0]) {
                $browser->waitUntilMissing(self::INVITATION_MANAGE_MODAL, self::WAIT_FOR_SECONDS);
            }
        }
    }
}
