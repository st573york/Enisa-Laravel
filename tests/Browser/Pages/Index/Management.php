<?php

namespace Tests\Browser\Pages\Index;

use Tests\Browser\Components\Loader;
use Tests\Browser\Components\Button;
use Tests\Browser\Components\Dropdown;
use Tests\Browser\Components\InputField;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class Management extends Page
{
    const WAIT_FOR_SECONDS = 10;
    const INDEXES_TABLE = '@indexes_table';
    const CREATE_INDEX = '@create_index';
    const SAVE_INDEX = '@save_index';
    const DELETE_INDEX = '@delete_index';
    const INDEX_MANAGE_MODAL = '@index_manage_modal';
    const INDEX_MANAGE_MODAL_TITLE = '@index_manage_modal_title';
    const INDEX_MANAGE_MODAL_DATA = '@index_manage_modal_data';
    const INDEX_MANAGE_MODAL_NAME = '@index_manage_modal_name';
    const INDEX_MANAGE_MODAL_NAME_INVALID = '@index_manage_modal_name_invalid';
    const INDEX_MANAGE_MODAL_DESCRIPTION = '@index_manage_modal_description';
    const INDEX_MANAGE_MODAL_YEAR = '@index_manage_modal_year';
    const INDEX_DELETE_MODAL_TEXT = '@index_delete_modal_text';
    const INDEX_MANAGE_MODAL_CLOSE = '@index_manage_modal_close';
    const INDEX_MANAGE_MODAL_PROCESS = '@index_manage_modal_process';

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/index/management';
    }

    /**
     * Assert the index management page.
     */
    public function assert(Browser $browser): void
    {
        $browser->on(new Loader(self::WAIT_FOR_SECONDS));
        $this->assertPath($browser);
        $browser->assertSeeIn('@indexes_title', 'Manage Indexes');
    }

    public function assertPath(Browser $browser): void
    {
        $browser->waitForLocation($this->url(), self::WAIT_FOR_SECONDS);
    }

    public function getIndexesData($indexes): array
    {
        $indexes_data = [];

        foreach ($indexes as $index) {
            $indexes_data[$index->id] = [
                'name' => $index->name,
                'year' => $index->year,
                'created_by' => $index->user,
                'status_text' => ($index->draft) ? 'Unpublished' : 'Published'
            ];
        }

        return $indexes_data;
    }

    /**
     * Assert the index management datatable.
     */
    public function assertDataTable(Browser $browser, $indexes_data = []): void
    {
        $browser->whenAvailable(self::INDEXES_TABLE, function ($table) use ($indexes_data) {
            $table->waitUntilMissingText('Loading...', self::WAIT_FOR_SECONDS);
            if (empty($indexes_data)) {
                $table->waitForText('No data available in table', self::WAIT_FOR_SECONDS);
            }
            elseif (array_key_exists('index_not_created', $indexes_data)) {
                $table->assertDontSee($indexes_data['index_not_created']);
            }
            else
            {
                foreach ($indexes_data as $index_id => $index_data)
                {
                    if (isset($index_data['deleted']) &&
                        $index_data['deleted'])
                    {
                        $table->assertMissing('@datatable_index_name_' . $index_id);
                    }
                    else
                    {
                        $table->assertScript("$(\"[dusk='" . substr('@datatable_index_name_' . $index_id, 1) . "']\").text();", $index_data['name'])
                              ->assertScript("$(\"[dusk='" . substr('@datatable_index_year_' . $index_id, 1) . "']\").text();", $index_data['year'])
                              ->assertScript("$(\"[dusk='" . substr('@datatable_index_created_by_' . $index_id, 1) . "']\").text();", $index_data['created_by'])
                              ->assertScript("$(\"[dusk='" . substr('@datatable_index_status_' . $index_id, 1) . "']\").text();", $index_data['status_text'])
                              ->assertEnabled('@datatable_edit_index_' . $index_id);
                        if ($index_data['status_text'] == 'Unpublished') {
                            $table->assertEnabled('@datatable_delete_index_' . $index_id);
                        }
                        else {
                            $table->assertDisabled('@datatable_delete_index_' . $index_id);
                        }
                    }
                }
            }
        });
    }

    public function assertCreateModal(Browser $browser, $data): void
    {
        $browser->whenAvailable(self::INDEX_MANAGE_MODAL, function ($modal) use ($data) {
            if (isset($data['title'])) {
                $modal->assertSeeIn(self::INDEX_MANAGE_MODAL_TITLE, $data['title']);
            }
            if (isset($data['name']))
            {
                if (isset($data['name']['error'])) {
                    $modal->waitForTextIn(self::INDEX_MANAGE_MODAL_NAME_INVALID, $data['name']['error']);
                }
                elseif (isset($data['name']['value'])) {
                    $modal->assertInputValue(self::INDEX_MANAGE_MODAL_NAME, $data['name']['value']);
                }
                $modal->assertEnabled(self::INDEX_MANAGE_MODAL_NAME);
            }
            if (isset($data['description']))
            {
                if (isset($data['description']['value'])) {
                    $modal->assertInputValue(self::INDEX_MANAGE_MODAL_DESCRIPTION, $data['description']['value']);
                }
                $modal->assertEnabled(self::INDEX_MANAGE_MODAL_DESCRIPTION);
            }
            if (isset($data['year']))
            {
                if (isset($data['year']['value'])) {
                    $modal->assertSelected(self::INDEX_MANAGE_MODAL_YEAR, $data['year']['value']);
                }
                $modal->assertEnabled(self::INDEX_MANAGE_MODAL_YEAR);
            }
            if (isset($data['actions'])) {
                $modal->assertSeeIn(self::INDEX_MANAGE_MODAL_CLOSE, 'Close')
                      ->assertEnabled(self::INDEX_MANAGE_MODAL_CLOSE)
                      ->assertSeeIn(self::INDEX_MANAGE_MODAL_PROCESS, 'Save changes')
                      ->assertEnabled(self::INDEX_MANAGE_MODAL_PROCESS);
            }
        });
    }

    public function assertDeleteModal(Browser $browser, $data): void
    {
        $browser->whenAvailable(self::INDEX_MANAGE_MODAL, function ($modal) use ($data) {
            if (isset($data['title'])) {
                $modal->assertSeeIn(self::INDEX_MANAGE_MODAL_TITLE, $data['title']);
            }
            if (isset($data['text'])) {
                $modal->assertSeeIn(self::INDEX_DELETE_MODAL_TEXT, $data['text']);
            }
            if (isset($data['actions'])) {
                $modal->assertSeeIn(self::INDEX_MANAGE_MODAL_CLOSE, 'Close')
                      ->assertEnabled(self::INDEX_MANAGE_MODAL_CLOSE)
                      ->assertSeeIn(self::INDEX_MANAGE_MODAL_PROCESS, 'Delete')
                      ->assertEnabled(self::INDEX_MANAGE_MODAL_PROCESS);
            }
        });
    }

    public function clickCreateIndex(Browser $browser): void
    {
        $browser->on(new Button(self::CREATE_INDEX))
                ->scrollAndClickButton('top')
                ->on(new Loader(self::WAIT_FOR_SECONDS));
    }

    public function clickEditIndex(Browser $browser, $index_id): void
    {
        $browser->within(self::INDEXES_TABLE, function ($table) use ($index_id) {
            $table->waitUntilMissingText('Loading...', self::WAIT_FOR_SECONDS);
            $table->on(new Button('@datatable_edit_index_' . $index_id))
                  ->scrollAndClickButton();
        });
    }

    public function clickDeleteIndex(Browser $browser, $name): void
    {
        $browser->within(self::INDEXES_TABLE, function ($table) use ($name) {
            $table->waitUntilMissingText('Loading...', self::WAIT_FOR_SECONDS);
            $table->on(new Button('@datatable_delete_index_' . $name))
                  ->scrollAndClickButton();
        });
    }

    public function createIndex(Browser $browser, $data): void
    {
        $close = (isset($data['action']) && $data['action'] == 'close') ? true : false;
        $save = (isset($data['action']) && $data['action'] == 'save') ? true : false;

        $browser->within(self::INDEX_MANAGE_MODAL, function ($modal) use ($data, $close, $save) {
            $modal->within(self::INDEX_MANAGE_MODAL_DATA, function ($modal) use ($data) {
                if (isset($data['name'])) {
                    $modal->on(new InputField(self::INDEX_MANAGE_MODAL_NAME, $data['name']))
                          ->scrollAndTypeInputField();
                }
                if (isset($data['description'])) {
                    $modal->on(new InputField(self::INDEX_MANAGE_MODAL_DESCRIPTION, $data['description']))
                          ->scrollAndTypeInputField();
                }
                if (isset($data['year'])) {
                    $modal->on(new Dropdown(self::INDEX_MANAGE_MODAL_YEAR, $data['year']))
                          ->scrollAndSelectDropdown();
                }
            });
            if ($close) {
                $modal->on(new Button(self::INDEX_MANAGE_MODAL_CLOSE))
                      ->clickButton();
            } elseif ($save) {
                $modal->on(new Button(self::INDEX_MANAGE_MODAL_PROCESS))
                      ->clickButton();
            }
        });
        if ($save)
        {
            $browser->on(new Loader(30));

            $is_invalid = $browser->script("return $(\"[dusk='" . substr(self::INDEX_MANAGE_MODAL, 1) . "']\").find('.invalid-feedback').is(':visible');");
            if (!$is_invalid[0]) {
                $browser->waitUntilMissing(self::INDEX_MANAGE_MODAL, self::WAIT_FOR_SECONDS);
            }
        }
    }

    public function deleteIndex(Browser $browser, $data): void
    {
        $delete = (isset($data['action']) && $data['action'] == 'delete') ? true : false;

        $browser->within(self::INDEX_MANAGE_MODAL, function ($modal) use ($delete) {
            if ($delete) {
                $modal->on(new Button(self::INDEX_MANAGE_MODAL_PROCESS))
                      ->clickButton();
            }
        });
        if ($delete) {
            $browser->on(new Loader(30))
                    ->waitUntilMissing(self::INDEX_MANAGE_MODAL, self::WAIT_FOR_SECONDS);
        }
    }
}
