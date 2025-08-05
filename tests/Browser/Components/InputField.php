<?php

namespace Tests\Browser\Components;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Component as BaseComponent;

class InputField extends BaseComponent
{
    protected $input;
    protected $value;

    public function __construct($input, $value)
    {
        $this->input = $input;
        $this->value = $value;
    }

    /**
     * Get the root selector for the component.
     */
    public function selector(): string
    {
        return $this->input;
    }

    /**
     * Get the value for the component.
     */
    public function value(): string
    {
        return $this->value;
    }

    public function typeInputField(Browser $browser): void
    {
        $browser->waitFor($this->selector())
                ->waitUntilEnabled($this->selector())
                ->type($this->selector(), $this->value())
                ->assertInputValue($this->selector(), $this->value());
    }

    public function scrollAndTypeInputField(Browser $browser, $scrollTo = null): void
    {
        $browser->waitFor($this->selector());
        if (!is_null($scrollTo)) {
            $browser->scrollToTopOrBottom($scrollTo);
        }
        else {
            $browser->scrollIntoView($this->selector());
        }
        $this->typeInputField($browser);
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
