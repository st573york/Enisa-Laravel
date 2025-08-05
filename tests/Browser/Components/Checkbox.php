<?php

namespace Tests\Browser\Components;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Component as BaseComponent;

class Checkbox extends BaseComponent
{
    protected $checkbox;

    public function __construct($checkbox)
    {
        $this->checkbox = $checkbox;
    }

    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return $this->checkbox;
    }

    public function clickCheckbox(Browser $browser): void
    {
        $browser->waitFor($this->selector())
                ->waitUntilEnabled($this->selector());
                
        if (str_contains($this->selector(), '@')) {
            $checked = $browser->script("return $(\"[dusk='" . substr($this->selector(), 1) . "']\").is(':checked');");
        }
        elseif (str_contains($this->selector(), '#')) {
            $checked = $browser->script("return $(\"" . $this->selector() . "\").is(':checked');");
        }
        if ($checked[0]) {
            $browser->uncheck($this->selector())
                    ->assertNotChecked($this->selector());
        }
        else {
            $browser->check($this->selector())
                    ->assertChecked($this->selector());
        }
    }

    public function scrollAndClickCheckbox(Browser $browser, $scrollTo = null): void
    {
        $browser->waitFor($this->selector());
        if (!is_null($scrollTo)) {
            $browser->scrollToTopOrBottom($scrollTo);
        }
        else {
            $browser->scrollIntoView($this->selector());
        }
        $this->clickCheckbox($browser);
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
