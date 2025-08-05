<?php

namespace Tests\Browser;

use Tests\Browser\Pages\Login;
use Tests\Browser\Components\Button;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LogoutTest extends DuskTestCase
{
    use DatabaseTransactions;

    public function test_logout()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAsRole('poc')
                    ->visit('/')
                    ->on(new Login)
                    ->on(new Button('@user'))
                    ->clickButton()
                    ->on(new Button('@logout'))
                    ->clickButton()
                    ->assertSee('You have been logged out');
        });
    }
}
