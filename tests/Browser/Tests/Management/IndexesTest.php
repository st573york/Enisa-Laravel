<?php

namespace Tests\Browser;

use App\Models\BaselineIndex;
use App\Models\IndexConfiguration;
use Tests\Browser\Pages\Login;
use Tests\Browser\Pages\Index\Edit;
use Tests\Browser\Pages\Index\Management;
use Tests\Browser\Components\Alert;
use Tests\Browser\Components\Button;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class IndexesTest extends DuskTestCase
{
    use DatabaseTransactions;

    public function test_create_index()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAsRole('admin');

            $two_years_ago = date('Y', strtotime('-2 year'));

            $management = new Management;

            $browser->visit('/')
                ->on(new Login)
                ->on(new Button('@management'))
                ->clickButton()
                ->on(new Button('@indexes'))
                ->clickButton();
            $management->assert($browser);
            $indexes = IndexConfiguration::getIndexConfigurations();
            $indexes_data = $management->getIndexesData($indexes);
            $management->assertDataTable($browser, $indexes_data);
            $management->clickCreateIndex($browser);
            $management->assertCreateModal($browser, [
                'title' => 'New Index',
                'name' => [
                    'value' => ''
                ],
                'description' => [
                    'value' => ''
                ],
                'year' => [
                    'value' => ''
                ]
            ]);
            $management->createIndex($browser, [
                'action' => 'save'
            ]);
            $management->assertCreateModal($browser, [
                'name' => [
                    'error' => 'The name field is required.'
                ],
                'year' => [
                    'error' => 'The year field is required.'
                ]
            ]);
            $management->createIndex($browser, [
                'name' => $indexes->first()->name,
                'year' => $indexes->skip(1)->first()->year,
                'action' => 'save'
            ]);
            $management->assertCreateModal($browser, [
                'name' => [
                    'error' => 'The name has already been taken.'
                ]
            ]);
            $name = Str::random(5);
            $management->createIndex($browser, [
                'name' => $name,
                'year' => $two_years_ago,
                'action' => 'save'
            ]);
            $browser->on(new Alert('Index for year ' . $two_years_ago . ' cannot be created. Please configure index first!'));
            $management->assertDataTable($browser, [
                'index_not_created' => $name
            ]);
            $management->clickCreateIndex($browser);
            $management->createIndex($browser, [
                'name' => Str::random(5),
                'description' => Str::random(20),
                'year' => $indexes->skip(2)->first()->year,
                'action' => 'save'
            ]);
            $indexes = IndexConfiguration::getIndexConfigurations();
            $indexes_data = $management->getIndexesData($indexes);
            $management->assertDataTable($browser, $indexes_data);
        });
    }

    public function test_edit_draft_index()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAsRole('admin');

            $indexes = IndexConfiguration::getIndexConfigurations();
            $published_index = IndexConfiguration::getExistingPublishedConfigurationForYear(date('Y'));
            $draft_index = IndexConfiguration::getExistingDraftConfigurationForYear(date('Y'));

            $edit = new Edit($draft_index->id);
            $management = new Management;

            $name = Str::random(5);
            $description = Str::random(5);

            $browser->visit('/')
                ->on(new Login)
                ->on(new Button('@management'))
                ->clickButton()
                ->on(new Button('@indexes'))
                ->clickButton();
            $management->assert($browser);
            $management->clickEditIndex($browser, $draft_index->id);
            $edit->assert($browser);
            $edit->assertData($browser, [
                'name' => [
                    'value' => $draft_index->name
                ],
                'description' => $draft_index->description,
                'year' => $draft_index->year,
                'status' => [
                    'draft' => $draft_index->draft,
                    'state' => 'enabled'
                ],
                'eu' => [
                    'published' => $draft_index->eu_published,
                    'state' => 'enabled'
                ],
                'ms' => [
                    'published' => $draft_index->ms_published,
                    'state' => 'disabled'
                ],
                'actions' => true
            ]);
            $edit->editIndex($browser, [
                'name' => '',
                'action' => 'save'
            ]);
            $edit->assertData($browser, [
                'name' => [
                    'error' => 'The name field is required.'
                ]
            ]);
            $edit->editIndex($browser, [
                'name' => $published_index->name,
                'action' => 'save'
            ]);
            $edit->assertData($browser, [
                'name' => [
                    'error' => 'The name has already been taken.'
                ]
            ]);
            $edit->editIndex($browser, [
                'name' => $draft_index->name,
                'click_status',
                'action' => 'save'
            ]);
            $browser->on(new Alert('Another official configuration already exists for this year!'));
            $edit->editIndex($browser, [
                'name' => $name,
                'description' => $description,
                'click_status',
                'action' => 'save'
            ]);
            $edit->editIndex($browser, [
                'click_eu'
            ]);
            $edit->assertData($browser, [
                'eu' => [
                    'published' => true,
                    'state' => 'enabled'
                ],
                'ms' => [
                    'published' => false,
                    'state' => 'enabled'
                ]
            ]);
            $edit->editIndex($browser, [
                'click_ms'
            ]);
            $edit->assertData($browser, [
                'eu' => [
                    'published' => true,
                    'state' => 'disabled'
                ],
                'ms' => [
                    'published' => true,
                    'state' => 'enabled'
                ]
            ]);
            $edit->editIndex($browser, [
                'click_ms'
            ]);
            $edit->assertData($browser, [
                'eu' => [
                    'published' => true,
                    'state' => 'enabled'
                ],
                'ms' => [
                    'published' => false,
                    'state' => 'enabled'
                ]
            ]);
            $edit->editIndex($browser, [
                'click_eu'
            ]);
            $edit->assertData($browser, [
                'eu' => [
                    'published' => false,
                    'state' => 'enabled'
                ],
                'ms' => [
                    'published' => false,
                    'state' => 'disabled'
                ]
            ]);
            $edit->editIndex($browser, [
                'click_eu',
                'click_ms',
                'action' => 'save'
            ]);
            $edit->assertData($browser, [
                'eu' => [
                    'published' => true,
                    'state' => 'disabled'
                ],
                'ms' => [
                    'published' => true,
                    'state' => 'enabled'
                ]
            ]);
            $edit->clickIndexesBreadcrumb($browser);
            $management->assert($browser);
            $indexes_data = $management->getIndexesData($indexes);
            $indexes_data[$draft_index->id]['name'] = $name;
            $indexes_data[$draft_index->id]['description'] = $description;
            $management->assertDataTable($browser, $indexes_data);
            $management->clickEditIndex($browser, $draft_index->id);
            $edit->editIndex($browser, [
                'action' => 'delete'
            ]);
            $edit->assertDeleteModal($browser, [
                'title' => 'Delete Index',
                'text' => "Index '" . $name . "' will be deleted. Are you sure?",
                'actions' => true
            ]);
            $edit->deleteIndex($browser, [
                'action' => 'delete'
            ]);
            $indexes_data[$draft_index->id]['deleted'] = true;
            $management->assertDataTable($browser, $indexes_data);
        });
    }

    public function test_edit_published_index()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAsRole('admin');

            $indexes = IndexConfiguration::getIndexConfigurations();
            $published_index = IndexConfiguration::getExistingPublishedConfigurationForYear(date('Y'));

            $baseline = $published_index->baseline;
            $published_index->baseline()->delete();

            $edit = new Edit($published_index->id);
            $management = new Management;

            $name = Str::random(5);
            $description = Str::random(5);

            $browser->visit('/')
                ->on(new Login)
                ->on(new Button('@management'))
                ->clickButton()
                ->on(new Button('@indexes'))
                ->clickButton();
            $management->assert($browser);
            $management->clickEditIndex($browser, $published_index->id);
            $edit->assert($browser);
            $edit->assertData($browser,
                [
                    'name' => [
                        'value' => $published_index->name
                    ],
                    'description' => $published_index->description,
                    'year' => $published_index->year,
                    'status' => [
                        'draft' => $published_index->draft,
                        'state' => 'enabled'
                    ],
                    'eu' => [
                        'published' => $published_index->eu_published,
                        'state' => 'disabled'
                    ],
                    'ms' => [
                        'published' => $published_index->ms_published,
                        'state' => 'enabled'
                    ],
                    'actions' => true
                ],
                false
            );
            $edit->editIndex($browser, [
                'click_status',
                'action' => 'save'
            ]);
            $edit->assertData($browser, [
                'actions' => true
            ]);
            $edit->editIndex($browser, [
                'name' => $name,
                'description' => $description,
                'click_status',
                'action' => 'save'
            ]);
            $edit->assertData($browser,
                [
                    'actions' => true
                ],
                false
            );
            $edit->clickIndexesBreadcrumb($browser);
            $management->assert($browser);
            $indexes_data = $management->getIndexesData($indexes);
            $indexes_data[$published_index->id]['name'] = $name;
            $indexes_data[$published_index->id]['description'] = $description;
            $management->assertDataTable($browser, $indexes_data);

            BaselineIndex::create([
                'name' => $baseline->name,
                'description' => $baseline->description,
                'index_configuration_id' => $baseline->index_configuration_id,
                'json_data' => $baseline->json_data,
                'report_json' => $baseline->report_json
            ]);

            $management->clickEditIndex($browser, $published_index->id);
            $edit->assert($browser);
            $edit->assertData($browser,
                [
                    'status' => [
                        'draft' => $published_index->draft,
                        'state' => 'disabled'
                    ]
                ],
                false
            );
        });
    }

    public function test_delete_draft_index()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAsRole('admin');
            
            $indexes = IndexConfiguration::getIndexConfigurations();
            $draft_index = IndexConfiguration::getExistingDraftConfigurationForYear(date('Y'));

            $management = new Management;

            $browser->visit('/')
                ->on(new Login)
                ->on(new Button('@management'))
                ->clickButton()
                ->on(new Button('@indexes'))
                ->clickButton();
            $management->assert($browser);
            $management->clickDeleteIndex($browser, $draft_index->id);
            $management->assertDeleteModal($browser, [
                'title' => 'Delete Index',
                'text' => "Index '" . $draft_index->name . "' will be deleted. Are you sure?",
                'actions' => true
            ]);
            $management->deleteIndex($browser, [
                'action' => 'delete'
            ]);
            $indexes_data = $management->getIndexesData($indexes);
            $indexes_data[$draft_index->id]['deleted'] = true;
            $management->assertDataTable($browser, $indexes_data);
        });
    }
}
