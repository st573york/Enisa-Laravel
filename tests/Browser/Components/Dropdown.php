<?php

namespace Tests\Browser\Components;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Component as BaseComponent;

class Dropdown extends BaseComponent
{
    protected $option;
    protected $value;

    public function __construct($option, $value)
    {
        $this->option = $option;
        $this->value = $value;
    }

    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return $this->option;
    }

    /**
     * Get the value for the component.
     */
    public function value(): string
    {
        return $this->value;
    }

    public function selectDropdown(Browser $browser): void
    {
        $browser->waitFor($this->selector())
                ->waitUntilEnabled($this->selector())
                ->select($this->selector(), $this->value())
                ->assertSelected($this->selector(), $this->value());
    }

    public function scrollAndSelectDropdown(Browser $browser, $scrollTo = null): void
    {
        $browser->waitFor($this->selector());
        if (!is_null($scrollTo)) {
            $browser->scrollToTopOrBottom($scrollTo);
        }
        else {
            $browser->scrollIntoView($this->selector());
        }
        $this->selectDropdown($browser);
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
