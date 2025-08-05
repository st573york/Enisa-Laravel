<?php

namespace Tests\Browser\Pages;

use Tests\Browser\Components\Loader;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class MyAccount extends Page
{
    const WAIT_FOR_SECONDS = 10;
    const USER_DATA = '@user_data';
    const COUNTRY = '@country';
    const COUNTRY_INVALID = '@country_invalid';
    const SAVE_CHANGES = '@save_changes';

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/my-account';
    }

    /**
     * Assert the my account page.
     */
    public function assert(Browser $browser): void
    {
        $browser->on(new Loader(self::WAIT_FOR_SECONDS))
                ->waitForLocation($this->url(), self::WAIT_FOR_SECONDS)
                ->assertSee('My Account')
                ->within(self::USER_DATA, function ($table) {
                    $table->assertDisabled('@name')
                          ->assertDisabled('@email')
                          ->assertEnabled(self::COUNTRY);
                });
    }
}
