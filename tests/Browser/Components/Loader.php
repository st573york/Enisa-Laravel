<?php

namespace Tests\Browser\Components;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Component as BaseComponent;

class Loader extends BaseComponent
{
    protected $seconds;

    public function __construct($seconds)
    {
        $this->seconds = $seconds;
    }

    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return '@loader';
    }

    /**
     * Assert the loader is visible on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPresent($this->selector())
                ->waitUntilMissing($this->selector(), $this->seconds);
    }

    /**
     * Get the element shortcuts for the component.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [];
    }

    /**
     * Get the global element shortcuts for the site.
     *
     * @return array<string, string>
     */
    public static function siteElements(): array
    {
        return [];
    }
}
