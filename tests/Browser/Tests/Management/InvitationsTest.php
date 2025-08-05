<?php

namespace Tests\Browser\Invitation;

use App\HelperFunctions\GeneralHelper;
use App\HelperFunctions\InvitationHelper;
use App\HelperFunctions\TestHelper;
use App\HelperFunctions\UserPermissions;
use App\Models\Country;
use App\Models\Invitation;
use Tests\Browser\Pages\Login;
use Tests\Browser\Pages\Invitation\Management;
use Tests\Browser\Components\Button;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class InvitationsTest extends DuskTestCase
{
    use DatabaseTransactions;

    public function test_admin_hash_invalid()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;
            
            $country = $user->permissions->first()->country->name;
            $role = $user->permissions->first()->role->name;

            $datatable_data = [];

            $management = new Management;
            
            $invitation = InvitationHelper::storeInvitation([
                'name' => $user->name,
                'email' => $user->email,
                'country' => $country,
                'role' => $role
            ]);

            $browser->visit('/index/access?hash=' . Str::random(10))
                    ->assertSeeIn('@error_title', 'Unauthorized User')
                    ->assertSeeIn('@error_message', 'Sorry, something went wrong.')
                    ->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@invitations'))
                    ->clickButton();
            $management->assert($browser);
            $datatable_data[$invitation->id] = [
                'name' => $user->name,
                'email' => $user->email,
                'country' => $country,
                'role' => $role,
                'invited_by' => $user->name,
                'invited_at' => GeneralHelper::dateFormat($invitation->invited_at, 'd-m-Y'),
                'registered_at' => '-',
                'status_text' => 'Pending'
            ];
            $management->assertDataTable($browser, $datatable_data);
        });
    }

    public function test_admin_hash_expired()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;

            $country = $user->permissions->first()->country->name;
            $role = $user->permissions->first()->role->name;

            $invitation = InvitationHelper::storeInvitation([
                'name' => $user->name,
                'email' => $user->email,
                'country' => $country,
                'role' => $role
            ]);
            $invitation->expired_at = date('Y-m-d', strtotime(date('Y-m-d') . ' - 1 days'));
            $invitation->save();

            $datatable_data = [];

            $management = new Management;

            $browser->visit('/index/access?hash=' . $invitation->hash)
                    ->assertSeeIn('@error_title', 'Invitation expired')
                    ->assertSeeIn('@error_message', 'Your invitation link has expired. Please contact your country\'s Primary Point of Contact to request a new invitation link.')
                    ->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@invitations'))
                    ->clickButton();
            $management->assert($browser);
            $datatable_data[$invitation->id] = [
                'name' => $user->name,
                'email' => $user->email,
                'country' => $country,
                'role' => $role,
                'invited_by' => $user->name,
                'invited_at' => GeneralHelper::dateFormat($invitation->invited_at, 'd-m-Y'),
                'registered_at' => '-',
                'status_text' => 'Expired'
            ];
            $management->assertDataTable($browser, $datatable_data);
        });
    }

    public function test_admin_hash_valid()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;

            $country = $user->permissions->first()->country->name;
            $role = $user->permissions->first()->role->name;

            $invitation = InvitationHelper::storeInvitation([
                'name' => $user->name,
                'email' => $user->email,
                'country' => $country,
                'role' => $role
            ]);

            $datatable_data = [];

            $management = new Management;
            
            $browser->visit('/index/access?hash=' . $invitation->hash)
                    ->on(new Login)
                    ->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@invitations'))
                    ->clickButton();
            $management->assert($browser);
            $datatable_data[$invitation->id] = [
                'name' => $user->name,
                'email' => $user->email,
                'country' => $country,
                'role' => $role,
                'invited_by' => $user->name,
                'invited_at' => GeneralHelper::dateFormat($invitation->invited_at, 'd-m-Y'),
                'registered_at' => GeneralHelper::dateFormat($invitation->registered_at, 'd-m-Y'),
                'status_text' => 'Registered'
            ];
            $management->assertDataTable($browser, $datatable_data);
        });
    }

    public function test_admin_invite_invalid()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;
            
            $poc = TestHelper::createNewUser([
                'trashed' => true,
                'permissions' => [
                    'role' => 'PoC',
                    'country' => Country::first()->name
                ]
            ]);

            $management = new Management;

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@invitations'))
                    ->clickButton();
            $management->assert($browser);
            $management->assertDataTable($browser);
            $management->clickInviteNewUser($browser);
            $management->assertInviteModal($browser, [
                'title' => 'Invite new user',
                'firstname' => [
                    'value' => ''
                ],
                'lastname' => [
                    'value' => ''
                ],
                'email' => [
                    'value' => ''
                ],
                'country' => [
                    'value' => ''
                ],
                'role' => [
                    'value' => ''
                ],
                'actions' => true
            ]);
            $management->inviteNewUser($browser, [
                'action' => 'invite'
            ]);
            $management->assertInviteModal($browser, [
                'firstname' => [
                    'error' => 'The first name field is required.'
                ],
                'lastname' => [
                    'error' => 'The last name field is required.'
                ],
                'email' => [
                    'error' => 'The email field is required.'
                ],
                'country' => [
                    'error' => 'The country field is required.'
                ],
                'role' => [
                    'error' => 'The role field is required.'
                ]
            ]);
            $management->inviteNewUser($browser, [
                'email' => Str::random(10),
                'action' => 'invite'
            ]);
            $management->assertInviteModal($browser, [
                'email' => [
                    'error' => 'The email must be a valid email address.'
                ]
            ]);
            $management->inviteNewUser($browser, [
                'email' => $user->email,
                'action' => 'invite'
            ]);
            $management->assertInviteModal($browser, [
                'email' => [
                    'error' => 'The email is already registered.'
                ]
            ]);
            $management->inviteNewUser($browser, [
                'email' => $poc->email,
                'action' => 'invite'
            ]);
            $management->assertInviteModal($browser, [
                'email' => [
                    'value' => $poc->email
                ]
            ]);
            $management->inviteNewUser($browser, [
                'action' => 'close'
            ]);
            $management->assertDataTable($browser);
        });
    }

    public function test_admin_invite_country_role()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;

            $expected_countries = UserPermissions::getUserCountries('name');
            $expected_roles = UserPermissions::getUserRoles('name');
            $country = Country::first()->name;
            $role = $user->permissions->first()->role->name;

            $management = new Management;

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@invitations'))
                    ->clickButton();
            $management->assert($browser);
            $management->assertDataTable($browser);
            $management->clickInviteNewUser($browser);
            // Select ENISA
            $management->inviteNewUser($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => config('constants.USER_GROUP')
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => ''
                ]
            ]);
            // Select Primary PoC
            $management->inviteNewUser($browser, [
                'role' => 'Primary PoC'
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => ''
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => 'Primary PoC'
                ]
            ]);
            // Select Country
            $management->inviteNewUser($browser, [
                'country' => $country
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => $country
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => 'Primary PoC'
                ]
            ]);
            // Select PoC
            $management->inviteNewUser($browser, [
                'role' => 'PoC'
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => $country
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => 'PoC'
                ]
            ]);
            // Select operator
            $management->inviteNewUser($browser, [
                'role' => 'operator'
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => $country
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => 'operator'
                ]
            ]);
            // Select viewer
            $management->inviteNewUser($browser, [
                'role' => 'viewer'
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => $country
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => 'viewer'
                ]
            ]);
            // Select ENISA
            $management->inviteNewUser($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => config('constants.USER_GROUP')
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => 'viewer'
                ]
            ]);
            // Select Primary PoC
            $management->inviteNewUser($browser, [
                'role' => 'Primary PoC'
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => ''
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => 'Primary PoC'
                ]
            ]);
            // Select ENISA
            $management->inviteNewUser($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => config('constants.USER_GROUP')
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => ''
                ]
            ]);
            // Select PoC
            $management->inviteNewUser($browser, [
                'role' => 'PoC'
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => ''
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => 'PoC'
                ]
            ]);
            // Select ENISA
            $management->inviteNewUser($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => config('constants.USER_GROUP')
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => ''
                ]
            ]);
            // Select operator
            $management->inviteNewUser($browser, [
                'role' => 'operator'
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => ''
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => 'operator'
                ]
            ]);
            // Select ENISA
            $management->inviteNewUser($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => config('constants.USER_GROUP')
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => ''
                ]
            ]);
            // Select admin
            $management->inviteNewUser($browser, [
                'role' => $role
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => config('constants.USER_GROUP')
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => $role
                ]
            ]);
        });
    }

    public function test_primary_poc_invite_country_role()
    {
        $this->artisan('db:seed --class=TestQuestionnaireUsersSeeder');

        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('ppoc')->user;

            $expected_countries = UserPermissions::getUserCountries('name');
            $expected_roles = UserPermissions::getUserRoles('name');

            $missing_countries = Country::where('id', '!=', $user->permissions->first()->country->id)->pluck('name')->toArray();
            $missing_roles = ['admin', 'Primary PoC'];
            $country = $user->permissions->first()->country->name;

            $management = new Management;

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@invitations'))
                    ->clickButton();
            $management->assert($browser);
            $management->assertDataTable($browser);
            $management->clickInviteNewUser($browser);
            $management->assertInviteModal($browser, [
                'country' => [
                    'missing_countries' => $missing_countries,
                    'expected_countries' => $expected_countries,
                    'value' => ''
                ],
                'role' => [
                    'missing_roles' => $missing_roles,
                    'expected_roles' => $expected_roles,
                    'value' => ''
                ]
            ]);
            // Select Country
            $management->inviteNewUser($browser, [
                'country' => $country
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'missing_countries' => $missing_countries,
                    'expected_countries' => $expected_countries,
                    'value' => $country
                ],
                'role' => [
                    'missing_roles' => $missing_roles,
                    'expected_roles' => $expected_roles,
                    'value' => ''
                ]
            ]);
            // Select PoC
            $management->inviteNewUser($browser, [
                'role' => 'PoC'
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'missing_countries' => $missing_countries,
                    'expected_countries' => $expected_countries,
                    'value' => $country
                ],
                'role' => [
                    'missing_roles' => $missing_roles,
                    'expected_roles' => $expected_roles,
                    'value' => 'PoC'
                ]
            ]);
            // Select operator
            $management->inviteNewUser($browser, [
                'role' => 'operator'
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'missing_countries' => $missing_countries,
                    'expected_countries' => $expected_countries,
                    'value' => $country
                ],
                'role' => [
                    'missing_roles' => $missing_roles,
                    'expected_roles' => $expected_roles,
                    'value' => 'operator'
                ]
            ]);
            // Select viewer
            $management->inviteNewUser($browser, [
                'role' => 'viewer'
            ]);
            $management->assertInviteModal($browser, [
                'country' => [
                    'missing_countries' => $missing_countries,
                    'expected_countries' => $expected_countries,
                    'value' => $country
                ],
                'role' => [
                    'missing_roles' => $missing_roles,
                    'expected_roles' => $expected_roles,
                    'value' => 'viewer'
                ]
            ]);
        });
    }

    public function test_admin_invite()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;

            $expected_countries = UserPermissions::getUserCountries('name');
            $expected_roles = UserPermissions::getUserRoles('name');
            $country = Country::first()->name;

            $poc = TestHelper::createNewUser([
                'trashed' => true,
                'permissions' => [
                    'role' => 'PoC',
                    'country' => $country
                ]
            ]);

            $firstname = $poc->firstName;
            $lastname = $poc->lastName;
            $email = $poc->email;
            $role = $poc->permissions()->withTrashed()->first()->role->name;
            $datatable_data = [];

            $management = new Management;

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@invitations'))
                    ->clickButton();
            $management->assert($browser);
            $management->assertDataTable($browser);
            $management->clickInviteNewUser($browser);
            $management->inviteNewUser($browser, [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'country' => $country,
                'role' => $role
            ]);
            $management->assertInviteModal($browser, [
                'firstname' => [
                    'value' => $firstname
                ],
                'lastname' => [
                    'value' => $lastname
                ],
                'email' => [
                    'value' => $email
                ],
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => $country
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => $role
                ]
            ]);
            $management->inviteNewUser($browser, [
                'action' => 'invite'
            ]);
            // Invite twice
            $management->clickInviteNewUser($browser);
            $management->inviteNewUser($browser, [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'country' => $country,
                'role' => $role
            ]);
            $management->assertInviteModal($browser, [
                'firstname' => [
                    'value' => $firstname
                ],
                'lastname' => [
                    'value' => $lastname
                ],
                'email' => [
                    'value' => $email
                ],
                'country' => [
                    'expected_countries' => $expected_countries,
                    'value' => $country
                ],
                'role' => [
                    'expected_roles' => $expected_roles,
                    'value' => $role
                ]
            ]);
            $management->inviteNewUser($browser, [
                'action' => 'invite'
            ]);
            $invitations = Invitation::getInvitations();
            foreach ($invitations as $invitation) {
                $datatable_data[$invitation->id] = [
                    'name' => $firstname . ' ' . $lastname,
                    'email' => $email,
                    'country' => $country,
                    'role' => $role,
                    'invited_by' => $user->name,
                    'invited_at' => GeneralHelper::dateFormat($invitation->invited_at, 'd-m-Y'),
                    'registered_at' => '-',
                    'status_text' => 'Pending'
                ];
            }
            $management->assertDataTable($browser, $datatable_data);
        });
    }
}
