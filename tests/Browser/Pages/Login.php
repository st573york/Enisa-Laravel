<?php

namespace Tests\Browser\Pages;

use App\HelperFunctions\IndexReportsHelper;
use App\Models\BaselineIndex;
use App\Models\IndexConfiguration;
use Tests\Browser\Components\Loader;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class Login extends Page
{
    const WAIT_FOR_SECONDS = 10;

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/index/access';
    }

    /**
     * Assert the login page.
     */
    public function assert(Browser $browser): void
    {
        $index = IndexConfiguration::getLatestPublishedConfiguration();
        $indices = IndexReportsHelper::getIndiceReports($index);
        $baseline_index = BaselineIndex::where('index_configuration_id', $index->id)->first();
        
        $browser->cookie('index-year', $index->year)
                ->refresh()
                ->assertHasCookie('index-year')
                ->on(new Loader(self::WAIT_FOR_SECONDS))
                ->waitForLocation($this->url(), self::WAIT_FOR_SECONDS);
        if (empty($indices) && is_null($baseline_index)) {
            $browser->assertSee('No data available. Data collection for ' . $index->year . ' is currently in progress.');
        }
    }
}
