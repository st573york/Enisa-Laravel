<?php

namespace Tests\Browser;

use Tests\Browser\Pages\Login;
use Tests\Browser\Pages\MyAccount;
use Tests\Browser\Components\Alert;
use Tests\Browser\Components\Button;
use Tests\Browser\Components\Dropdown;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MyAccountTest extends DuskTestCase
{
    use DatabaseTransactions;
    
    public function test_poc_edit_my_account()
    {
        $this->browse(function (Browser $browser) {
            $saveChanges = new Button(MyAccount::SAVE_CHANGES);

            $browser->loginAsRole('poc')
                    ->visit('/')
                    ->on(new Login)
                    ->on(new Button('@user'))
                    ->clickButton()
                    ->on(new Button('@my_account'))
                    ->clickButton()
                    ->on(new MyAccount);
                    // No Country
                    $saveChanges->scrollAndClickButton($browser);
                    $browser->within(MyAccount::USER_DATA, function ($browser) {
                        $browser->waitForTextIn(MyAccount::COUNTRY_INVALID, 'The country field is required.', MyAccount::WAIT_FOR_SECONDS);
                    })
                    // Country
                    ->within(MyAccount::USER_DATA, function ($browser) {
                        $browser->on(new Dropdown(MyAccount::COUNTRY, 'GRC'))
                                ->scrollAndSelectDropdown();
                    });
                    // Save changes successfully
                    $saveChanges->scrollAndClickButton($browser);
                    $browser->within(MyAccount::USER_DATA, function ($browser) {
                        $browser->waitUntilDisabled(MyAccount::COUNTRY, MyAccount::WAIT_FOR_SECONDS);
                    })
                    ->on(new Alert('User details have been successfully updated!'));
        });
    }
}
