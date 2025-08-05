<?php

namespace Tests\Browser\Components;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Component as BaseComponent;

class Button extends BaseComponent
{
    const WAIT_FOR_SECONDS = 10;

    protected $button;
    protected $text;

    public function __construct($button, $text = '')
    {
        $this->button = $button;
        $this->text = $text;
    }

    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return $this->button;
    }

    public function clickButton(Browser $browser): void
    {
        $browser->waitFor($this->selector())
                ->waitUntilEnabled($this->selector());
        if (!empty($this->text)) {
            $browser->assertSeeIn($this->selector(), $this->text);
        }
        $browser->press($this->selector());
    }

    public function scrollAndClickButton(Browser $browser, $scrollTo = null): void
    {
        $browser->waitFor($this->selector());
        if (!is_null($scrollTo)) {
            $browser->scrollToTopOrBottom($scrollTo);
        }
        else {
            $browser->scrollIntoView($this->selector());
        }
        $this->clickButton($browser);
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
