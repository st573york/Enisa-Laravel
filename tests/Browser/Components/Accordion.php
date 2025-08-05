<?php

namespace Tests\Browser\Components;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Component as BaseComponent;

class Accordion extends BaseComponent
{
    const WAIT_FOR_SECONDS = 10;

    protected $accordion;
    protected $body;

    public function __construct($accordion, $body = null)
    {
        $this->accordion = $accordion;
        $this->body = $body;
    }

    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return $this->accordion;
    }

    public function clickAccordion(Browser $browser): void
    {
        $browser->waitFor($this->selector())
                ->waitUntilEnabled($this->selector())
                // Click accordion again if the div has not expanded for max seconds
                ->waitUsing(self::WAIT_FOR_SECONDS, 1, function () use ($browser) {
                    $browser->script("$(\"[dusk='" . substr($this->selector(), 1) . "']\").click();");

                    if (!is_null($this->body)) {
                        $browser->assertScript("$(\"[dusk='" . substr($this->body, 1) . "']\").hasClass('show');", true);
                    }

                    return true;
                });
    }

    public function scrollAndClickAccordion(Browser $browser, $scrollTo = null): void
    {
        $browser->waitFor($this->selector());
        if (!is_null($scrollTo)) {
            $browser->scrollToTopOrBottom($scrollTo);
        }
        else {
            $browser->scrollIntoView($this->selector());
        }
        $this->clickAccordion($browser);
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
