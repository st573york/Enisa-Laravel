<?php

namespace Tests\Browser\Components;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Component as BaseComponent;

class RadioButton extends BaseComponent
{
    protected $radio;
    protected $value;

    public function __construct($radio, $value)
    {
        $this->radio = $radio;
        $this->value = $value;
    }

    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return $this->radio;
    }

    /**
     * Get the value for the component.
     */
    public function value(): string
    {
        return $this->value;
    }

    public function clickRadioButton(Browser $browser): void
    {
        $browser->waitFor($this->selector())
                ->waitUntilEnabled($this->selector())
                ->radio($this->selector(), $this->value())
                ->assertRadioSelected($this->selector(), $this->value());
    }

    public function scrollAndClickRadioButton(Browser $browser, $scrollTo = null): void
    {
        $browser->waitFor($this->selector());
        if (!is_null($scrollTo)) {
            $browser->scrollToTopOrBottom($scrollTo);
        }
        else {
            $browser->scrollIntoView($this->selector());
        }
        $this->clickRadioButton($browser);
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
