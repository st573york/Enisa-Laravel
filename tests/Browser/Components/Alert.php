<?php

namespace Tests\Browser\Components;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Component as BaseComponent;

class Alert extends BaseComponent
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return '@alert';
    }

    /**
     * Assert the alert is visible on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->whenAvailable($this->selector(), function ($alert) {
            $alert->assertScript("$(\"[dusk='" . substr($this->selector(), 1) . "']\").text().replace(/\s\s+/g, ' ');", $this->message);
        });
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
