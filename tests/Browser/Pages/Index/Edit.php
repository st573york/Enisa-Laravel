<?php

namespace Tests\Browser\Pages\Index;

use Tests\Browser\Components\Loader;
use Tests\Browser\Components\Button;
use Tests\Browser\Components\InputField;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class Edit extends Page
{
    const WAIT_FOR_SECONDS = 10;
    const INDEXES_BREADCRUMB = '@indexes_breadcrumb';
    const SAVE_INDEX = '@save_index';
    const DELETE_INDEX = '@delete_index';
    const EDIT_INDEX_DATA = '@edit_index_data';
    const EDIT_INDEX_NAME = '@edit_index_name';
    const EDIT_INDEX_NAME_INVALID = '@edit_index_name_invalid';
    const EDIT_INDEX_DESCRIPTION = '@edit_index_description';
    const EDIT_INDEX_YEAR = '@edit_index_year';
    const EDIT_INDEX_STATUS_SWITCH = '@edit_index_status_switch';
    const EDIT_INDEX_STATUS_TEXT = '@edit_index_status_text';
    const EDIT_INDEX_EU_SWITCH = '@edit_index_eu_switch';
    const EDIT_INDEX_EU_TEXT = '@edit_index_eu_text';
    const EDIT_INDEX_MS_SWITCH = '@edit_index_ms_switch';
    const EDIT_INDEX_MS_TEXT = '@edit_index_ms_text';
    const EDIT_INDEX_MODAL = '@edit_index_modal';
    const EDIT_INDEX_MODAL_TITLE = '@edit_index_modal_title';
    const DELETE_INDEX_MODAL_TEXT = '@delete_index_modal_text';
    const EDIT_INDEX_MODAL_CLOSE = '@edit_index_modal_close';
    const EDIT_INDEX_MODAL_PROCESS = '@edit_index_modal_process';

    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/index/show/' . $this->id;
    }

    /**
     * Assert the index edit page.
     */
    public function assert(Browser $browser): void
    {
        $browser->on(new Loader(self::WAIT_FOR_SECONDS));
        $this->assertPath($browser);
        $browser->assertSeeIn('@index_title', 'Edit Index');
    }

    public function assertPath(Browser $browser): void
    {
        $browser->waitForLocation($this->url(), self::WAIT_FOR_SECONDS);
    }

    public function assertData(Browser $browser, $data = [], $draft = true): void
    {
        if (isset($data['actions']) &&
            $data['actions'])
        {
            $browser->assertSeeIn(self::SAVE_INDEX, 'Save changes')
                    ->assertEnabled(self::SAVE_INDEX)
                    ->assertSeeIn(self::DELETE_INDEX, 'Delete');
            if ($draft) {
                $browser->assertEnabled(self::DELETE_INDEX);
            }
            else {
                $browser->assertDisabled(self::DELETE_INDEX);
            }
        }
        $browser->within(self::EDIT_INDEX_DATA, function ($section) use ($data, $draft) {
            if ($draft) {
                $section->assertEnabled(self::EDIT_INDEX_NAME)
                        ->assertEnabled(self::EDIT_INDEX_DESCRIPTION)
                        ->assertEnabled(self::EDIT_INDEX_YEAR);
            }
            else {
                $section->assertDisabled(self::EDIT_INDEX_NAME)
                        ->assertDisabled(self::EDIT_INDEX_DESCRIPTION)
                        ->assertDisabled(self::EDIT_INDEX_YEAR);
            }
            if (isset($data['name']))
            {
                if (isset($data['name']['error'])) {
                    $section->waitForTextIn(self::EDIT_INDEX_NAME_INVALID, $data['name']['error']);
                }
                elseif (isset($data['name']['value'])) {
                    $section->assertInputValue(self::EDIT_INDEX_NAME, $data['name']['value']);
                }
            }
            if (isset($data['description'])) {
                $section->assertInputValue(self::EDIT_INDEX_DESCRIPTION, $data['description']);
            }
            if (isset($data['year'])) {
                $section->assertSelected(self::EDIT_INDEX_YEAR, $data['year']);
            }
            if (isset($data['status']))
            {
                if (isset($data['status']['draft'])) {
                    $section->assertSeeIn(self::EDIT_INDEX_STATUS_TEXT, ($data['status']['draft'] ? 'Unpublished' : 'Published'))
                            ->assertScript("$(\"[dusk='" . substr(self::EDIT_INDEX_STATUS_SWITCH, 1) . "']\").is(':checked');", ($data['status']['draft']) ? false : true);
                }
                if (isset($data['status']['state'])) {
                    $section->assertScript("$(\"[dusk='" . substr(self::EDIT_INDEX_STATUS_SWITCH, 1) . "']\").hasClass('switch-deactivated');", ($data['status']['state'] == 'disabled') ? true : false);
                }
            }
            if (isset($data['eu']))
            {
                if (isset($data['eu']['published'])) {
                    $section->assertSeeIn(self::EDIT_INDEX_EU_TEXT, ($data['eu']['published'] ? 'Published' : 'Unpublished'))
                            ->assertScript("$(\"[dusk='" . substr(self::EDIT_INDEX_EU_SWITCH, 1) . "']\").is(':checked');", ($data['eu']['published']) ? true : false);
                }
                if (isset($data['eu']['state'])) {
                    $section->assertScript("$(\"[dusk='" . substr(self::EDIT_INDEX_EU_SWITCH, 1) . "']\").hasClass('switch-deactivated');", ($data['eu']['state'] == 'disabled') ? true : false);
                }
            }
            if (isset($data['ms']))
            {
                if (isset($data['ms']['published'])) {
                    $section->assertSeeIn(self::EDIT_INDEX_MS_TEXT, ($data['ms']['published'] ? 'Published' : 'Unpublished'))
                            ->assertScript("$(\"[dusk='" . substr(self::EDIT_INDEX_MS_SWITCH, 1) . "']\").is(':checked');", ($data['ms']['published']) ? true : false);
                }
                if (isset($data['ms']['state'])) {
                    $section->assertScript("$(\"[dusk='" . substr(self::EDIT_INDEX_MS_SWITCH, 1) . "']\").hasClass('switch-deactivated');", ($data['ms']['state'] == 'disabled') ? true : false);
                }
            }
        });
    }

    public function assertDeleteModal(Browser $browser, $data): void
    {
        $browser->whenAvailable(self::EDIT_INDEX_MODAL, function ($modal) use ($data) {
            if (isset($data['title'])) {
                $modal->assertSeeIn(self::EDIT_INDEX_MODAL_TITLE, $data['title']);
            }
            if (isset($data['text'])) {
                $modal->assertSeeIn(self::DELETE_INDEX_MODAL_TEXT, $data['text']);
            }
            if (isset($data['actions'])) {
                $modal->assertSeeIn(self::EDIT_INDEX_MODAL_CLOSE, 'Close')
                      ->assertEnabled(self::EDIT_INDEX_MODAL_CLOSE)
                      ->assertSeeIn(self::EDIT_INDEX_MODAL_PROCESS, 'Delete')
                      ->assertEnabled(self::EDIT_INDEX_MODAL_PROCESS);
            }
        });
    }

    public function clickIndexesBreadcrumb(Browser $browser): void
    {
        $browser->on(new Button(self::INDEXES_BREADCRUMB))
                ->scrollAndClickButton('top');
    }

    public function editIndex(Browser $browser, $data): void
    {
        $save = (isset($data['action']) && $data['action'] == 'save') ? true : false;
        $delete = (isset($data['action']) && $data['action'] == 'delete') ? true : false;

        $browser->within(self::EDIT_INDEX_DATA, function ($section) use ($data) {
            if (isset($data['name'])) {
                $section->on(new InputField(self::EDIT_INDEX_NAME, $data['name']))
                        ->scrollAndTypeInputField();
            }
            if (isset($data['description'])) {
                $section->on(new InputField(self::EDIT_INDEX_DESCRIPTION, $data['description']))
                        ->scrollAndTypeInputField();
            }
            if (in_array('click_status', $data)) {
                $section->on(new Button(self::EDIT_INDEX_STATUS_SWITCH))
                        ->clickButton();
            }
            if (in_array('click_eu', $data)) {
                $section->on(new Button(self::EDIT_INDEX_EU_SWITCH))
                        ->clickButton();
            }
            if (in_array('click_ms', $data)) {
                $section->on(new Button(self::EDIT_INDEX_MS_SWITCH))
                        ->clickButton();
            }
        });
        if ($save) {
            $browser->on(new Button(self::SAVE_INDEX))
                    ->scrollAndClickButton('top')
                    ->on(new Loader(self::WAIT_FOR_SECONDS));
        }
        elseif ($delete) {
            $browser->on(new Button(self::DELETE_INDEX))
                    ->scrollAndClickButton('top');
        }
    }

    public function deleteIndex(Browser $browser, $data): void
    {
        $delete = (isset($data['action']) && $data['action'] == 'delete') ? true : false;

        $browser->within(self::EDIT_INDEX_MODAL, function ($modal) use ($delete) {
            if ($delete) {
                $modal->on(new Button(self::EDIT_INDEX_MODAL_PROCESS))
                      ->clickButton();
            }
        });
        if ($delete) {
            $browser->on(new Loader(30))
                    ->waitUntilMissing(self::EDIT_INDEX_MODAL, self::WAIT_FOR_SECONDS);
        }
    }
}
