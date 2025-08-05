<?php

namespace Tests\Browser\Components;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Component as BaseComponent;

class TinyMCE extends BaseComponent
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

    public function typeTinyMCE(Browser $browser): void
    {
        $browser->script('
            let editor = tinymce.get("' . $this->selector() . '");
            editor.setContent("' . $this->value() . '");
        ');
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
