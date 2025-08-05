<?php

namespace Tests\Browser;

use Tests\Browser\Pages\Login;
use Tests\Browser\Pages\Documents;
use Tests\Browser\Components\Button;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DocumentsTest extends DuskTestCase
{
    use DatabaseTransactions;

    public function test_admin_documents()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@documents'))
                    ->clickButton();
            $documents = new Documents($user);
            $documents->assert($browser);
        });
    }

    public function test_poc_documents()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('poc')->user;

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@documents'))
                    ->clickButton();
            $documents = new Documents($user);
            $documents->assert($browser);
        });
    }
}
