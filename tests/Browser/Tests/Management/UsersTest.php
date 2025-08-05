<?php

namespace Tests\Browser\User;

use App\HelperFunctions\TestHelper;
use App\HelperFunctions\UserPermissions;
use App\Models\Country;
use App\Models\User;
use Tests\Browser\Pages\Login;
use Tests\Browser\Pages\User\Management;
use Tests\Browser\Components\Alert;
use Tests\Browser\Components\Button;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class UsersTest extends DuskTestCase
{
    use DatabaseTransactions;
    
    public function test_admin_edit_country_role()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;

            $expected_countries = UserPermissions::getUserCountries('name');
            $expected_roles = UserPermissions::getUserRoles('name');
            $country = Country::first()->name;
            $role = $user->permissions->first()->role->name;

            $users = User::getUsers();

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@users'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $datatable_data = $management->getDataTableData($users);
            $management->assertDataTable($browser, $datatable_data);
            $management->clickEditUser($browser, $user->id);
            $management->assertEditModal($browser, [
                'title' => 'Edit User ' . $user->name,
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => $role,
                'status_switch' => 'disabled',
                'status_text' => 'Enabled',
                'actions' => true
            ]);
            // Select country
            $management->editSingleOrMultipleUsers($browser, [
                'country' => $country
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => ''
            ]);
            // Select Primary PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'Primary PoC'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => 'Primary PoC'
            ]);
            // Select PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'PoC'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => 'PoC'
            ]);
            // Select operator
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'operator'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => 'operator'
            ]);
            // Select viewer
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'viewer'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => 'viewer'
            ]);
            // Select ENISA
            $management->editSingleOrMultipleUsers($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => 'viewer'
            ]);
            // Select Primary PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'Primary PoC'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => '',
                'expected_roles' => $expected_roles,
                'role' => 'Primary PoC'
            ]);
            // Select ENISA
            $management->editSingleOrMultipleUsers($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => ''
            ]);
            // Select PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'PoC'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => '',
                'expected_roles' => $expected_roles,
                'role' => 'PoC'
            ]);
            // Select ENISA
            $management->editSingleOrMultipleUsers($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => ''
            ]);
            // Select operator
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'operator'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => '',
                'expected_roles' => $expected_roles,
                'role' => 'operator'
            ]);
            // Select ENISA
            $management->editSingleOrMultipleUsers($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => ''
            ]);
            // Select admin
            $management->editSingleOrMultipleUsers($browser, [
                'role' => $role
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => $role
            ]);
        });
    }

    public function test_admin_edit_admin_country_role()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;

            $expected_countries = UserPermissions::getUserCountries('name');
            $expected_roles = UserPermissions::getUserRoles('name');
            $country = Country::first()->name;
            $role = $user->permissions->first()->role->name;

            $admin = TestHelper::createNewUser([
                'permissions' => [
                    'role' => $role,
                    'country' => config('constants.USER_GROUP')
                ]
            ]);

            $users = User::getUsers();

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@users'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $datatable_data = $management->getDataTableData($users);
            $management->assertDataTable($browser, $datatable_data);
            $management->clickEditUser($browser, $admin->id);
            $management->assertEditModal($browser, [
                'title' => 'Edit User ' . $admin->name,
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => $admin->permissions->first()->role->name,
                'status_switch' => 'enabled',
                'status_text' => 'Enabled',
                'actions' => true
            ]);
            // Select country
            $management->editSingleOrMultipleUsers($browser, [
                'country' => $country
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => ''
            ]);
            // Select Primary PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'Primary PoC'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => 'Primary PoC'
            ]);
            // Select PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'PoC'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => 'PoC'
            ]);
            // Select operator
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'operator'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => 'operator'
            ]);
            // Select viewer
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'viewer'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => 'viewer'
            ]);
            // Select ENISA
            $management->editSingleOrMultipleUsers($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => 'viewer'
            ]);
            // Select Primary PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'Primary PoC'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => '',
                'expected_roles' => $expected_roles,
                'role' => 'Primary PoC'
            ]);
            // Select ENISA
            $management->editSingleOrMultipleUsers($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => ''
            ]);
            // Select PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'PoC'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => '',
                'expected_roles' => $expected_roles,
                'role' => 'PoC'
            ]);
            // Select ENISA
            $management->editSingleOrMultipleUsers($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => ''
            ]);
            // Select operator
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'operator'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => '',
                'expected_roles' => $expected_roles,
                'role' => 'operator'
            ]);
            // Select ENISA
            $management->editSingleOrMultipleUsers($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => ''
            ]);
            // Select admin
            $management->editSingleOrMultipleUsers($browser, [
                'role' => $role
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => $role
            ]);
        });
    }

    public function test_admin_edit_primary_poc_country_role()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;

            $expected_countries = UserPermissions::getUserCountries('name');
            $expected_roles = UserPermissions::getUserRoles('name');
            $country = Country::first()->name;
            $other_country = Country::skip(1)->first()->name;

            $ppoc = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'Primary PoC',
                    'country' => $country
                ]
            ]);

            $role = $ppoc->permissions->first()->role->name;

            $users = User::getUsers();

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@users'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $datatable_data = $management->getDataTableData($users);
            $management->assertDataTable($browser, $datatable_data);
            $management->clickEditUser($browser, $ppoc->id);
            $management->assertEditModal($browser, [
                'title' => 'Edit User ' . $ppoc->name,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => $role,
                'status_switch' => 'enabled',
                'status_text' => 'Enabled',
                'actions' => true
            ]);
            // Select other country
            $management->editSingleOrMultipleUsers($browser, [
                'country' => $other_country
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $other_country,
                'expected_roles' => $expected_roles,
                'role' => $role
            ]);
            // Select PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'PoC'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $other_country,
                'expected_roles' => $expected_roles,
                'role' => 'PoC'
            ]);
            // Select operator
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'operator'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $other_country,
                'expected_roles' => $expected_roles,
                'role' => 'operator'
            ]);
            // Select viewer
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'viewer'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $other_country,
                'expected_roles' => $expected_roles,
                'role' => 'viewer'
            ]);
            // Select ENISA
            $management->editSingleOrMultipleUsers($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => 'viewer'
            ]);
            // Select Primary PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'Primary PoC'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => '',
                'expected_roles' => $expected_roles,
                'role' => 'Primary PoC'
            ]);
            // Select ENISA
            $management->editSingleOrMultipleUsers($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => ''
            ]);
            // Select PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'PoC'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => '',
                'expected_roles' => $expected_roles,
                'role' => 'PoC'
            ]);
            // Select ENISA
            $management->editSingleOrMultipleUsers($browser, [
                'country' => config('constants.USER_GROUP')
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => config('constants.USER_GROUP'),
                'expected_roles' => $expected_roles,
                'role' => ''
            ]);
            // Select operator
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'operator'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => '',
                'expected_roles' => $expected_roles,
                'role' => 'operator'
            ]);
            // Select country
            $management->editSingleOrMultipleUsers($browser, [
                'country' => $country
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => 'operator'
            ]);
            // Select Primary PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'Primary PoC'
            ]);
            $management->assertEditModal($browser, [
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => 'Primary PoC'
            ]);
        });
    }

    public function test_admin_edit_primary_poc_to_poc()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;

            $expected_countries = UserPermissions::getUserCountries('name');
            $expected_roles = UserPermissions::getUserRoles('name');
            $country = Country::first()->name;

            $ppoc = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'Primary PoC',
                    'country' => $country
                ]
            ]);

            $users = User::getUsers();

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@users'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $datatable_data = $management->getDataTableData($users);
            $management->assertDataTable($browser, $datatable_data);
            $management->clickEditUser($browser, $ppoc->id);
            $management->assertEditModal($browser, [
                'title' => 'Edit User ' . $ppoc->name,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => $ppoc->permissions->first()->role->name,
                'status_switch' => 'enabled',
                'status_text' => 'Enabled',
                'actions' => true
            ]);
            // Select PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'PoC',
                'action' => 'save'
            ]);
            $management->assertDataTable($browser, $datatable_data);
            $browser->on(new Alert('This user is the Primary PoC for ' . $country . ' and cannot be updated. Please first assign the Primary PoC role to another user for ' . $country . '!'));
        });
    }

    public function test_admin_edit_poc_to_primary_poc()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;

            $expected_countries = UserPermissions::getUserCountries('name');
            $expected_roles = UserPermissions::getUserRoles('name');
            $country = Country::skip(1)->first()->name;

            $ppoc = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'Primary PoC',
                    'country' => $country
                ]
            ]);

            $poc = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'PoC',
                    'country' => $country
                ]
            ]);

            $users = User::getUsers();

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@users'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $datatable_data = $management->getDataTableData($users);
            $management->assertDataTable($browser, $datatable_data);
            $management->clickEditUser($browser, $poc->id);
            $management->assertEditModal($browser, [
                'title' => 'Edit User ' . $poc->name,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'expected_roles' => $expected_roles,
                'role' => $poc->permissions->first()->role->name,
                'status_switch' => 'enabled',
                'status_text' => 'Enabled',
                'actions' => true
            ]);
            // Select Primary PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'Primary PoC',
                'action' => 'save'
            ]);
            $datatable_data[$ppoc->id]['role'] = 'PoC';
            $datatable_data[$poc->id]['role'] = 'Primary PoC';
            $management->assertDataTable($browser, $datatable_data);
        });
    }

    public function test_primary_poc_edit_country_role()
    {
        $this->artisan('db:seed --class=TestQuestionnaireUsersSeeder');

        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('ppoc')->user;

            $expected_countries = UserPermissions::getUserCountries('name');
            $expected_roles = UserPermissions::getUserRoles('name', true);

            $missing_countries = Country::where('id', '!=', $user->permissions->first()->country->id)->pluck('name')->toArray();
            $missing_roles = ['admin'];
            $country = $user->permissions->first()->country->name;

            $users = User::getUsers();

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@users'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $datatable_data = $management->getDataTableData($users);
            $management->assertDataTable($browser, $datatable_data);
            $management->clickEditUser($browser, $user->id);
            $management->assertEditModal($browser, [
                'title' => 'Edit User ' . $user->name,
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => $user->permissions->first()->role->name,
                'status_switch' => 'disabled',
                'status_text' => 'Enabled',
                'actions' => true
            ]);
            // Select PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'PoC'
            ]);
            $management->assertEditModal($browser, [
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => 'PoC'
            ]);
            // Select operator
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'operator'
            ]);
            $management->assertEditModal($browser, [
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => 'operator'
            ]);
            // Select viewer
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'viewer'
            ]);
            $management->assertEditModal($browser, [
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => 'viewer'
            ]);
            // Select Primary PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'Primary PoC'
            ]);
            $management->assertEditModal($browser, [
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => 'Primary PoC'
            ]);
        });
    }

    public function test_primary_poc_edit_poc_country_role()
    {
        $this->artisan('db:seed --class=TestQuestionnaireUsersSeeder');
        
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('ppoc')->user;

            $expected_countries = UserPermissions::getUserCountries('name');
            $expected_roles = UserPermissions::getUserRoles('name');

            $poc = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'PoC',
                    'country' => $user->permissions->first()->country->name
                ]
            ]);

            $missing_countries = Country::where('id', '!=', $poc->permissions->first()->country->id)->pluck('name')->toArray();
            $missing_roles = ['admin', 'Primary PoC'];
            $country = $poc->permissions->first()->country->name;

            $users = User::getUsers();

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@users'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $datatable_data = $management->getDataTableData($users);
            $management->assertDataTable($browser, $datatable_data);
            $management->clickEditUser($browser, $poc->id);
            $management->assertEditModal($browser, [
                'title' => 'Edit User ' . $poc->name,
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => $poc->permissions->first()->role->name,
                'status_switch' => 'enabled',
                'status_text' => 'Enabled',
                'actions' => true
            ]);
            // Select operator
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'operator'
            ]);
            $management->assertEditModal($browser, [
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => 'operator'
            ]);
            // Select viewer
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'viewer'
            ]);
            $management->assertEditModal($browser, [
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => 'viewer'
            ]);
            // Select PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'PoC'
            ]);
            $management->assertEditModal($browser, [
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => 'PoC'
            ]);
        });
    }

    public function test_poc_edit_country_role()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('poc')->user;

            $expected_countries = UserPermissions::getUserCountries('name');
            $expected_roles = UserPermissions::getUserRoles('name', true);

            $missing_countries = Country::where('id', '!=', $user->permissions->first()->country->id)->pluck('name')->toArray();
            $missing_roles = ['admin', 'Primary PoC'];
            $country = $user->permissions->first()->country->name;

            $users = User::getUsers();

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@users'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $datatable_data = $management->getDataTableData($users);
            $management->assertDataTable($browser, $datatable_data);
            $management->clickEditUser($browser, $user->id);
            $management->assertEditModal($browser, [
                'title' => 'Edit User ' . $user->name,
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => $user->permissions->first()->role->name,
                'status_switch' => 'disabled',
                'status_text' => 'Enabled',
                'actions' => true
            ]);
            // Select operator
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'operator'
            ]);
            $management->assertEditModal($browser, [
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => 'operator'
            ]);
            // Select viewer
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'viewer'
            ]);
            $management->assertEditModal($browser, [
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => 'viewer'
            ]);
            // Select PoC
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'PoC'
            ]);
            $management->assertEditModal($browser, [
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => 'PoC'
            ]);
        });
    }

    public function test_poc_edit_operator_country_role()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('poc')->user;

            $expected_countries = UserPermissions::getUserCountries('name');
            $expected_roles = UserPermissions::getUserRoles('name');

            $operator = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'operator',
                    'country' => $user->permissions->first()->country->name
                ]
            ]);

            $missing_countries = Country::where('id', '!=', $operator->permissions->first()->country->id)->pluck('name')->toArray();
            $missing_roles = ['admin', 'Primary PoC', 'PoC'];
            $country = $operator->permissions->first()->country->name;

            $users = User::getUsers();

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@users'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $datatable_data = $management->getDataTableData($users);
            $management->assertDataTable($browser, $datatable_data);
            $management->clickEditUser($browser, $operator->id);
            $management->assertEditModal($browser, [
                'title' => 'Edit User ' .$operator->name,
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => $operator->permissions->first()->role->name,
                'status_switch' => 'enabled',
                'status_text' => 'Enabled',
                'actions' => true
            ]);
            // Select viewer
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'viewer'
            ]);
            $management->assertEditModal($browser, [
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => 'viewer'
            ]);
            // Select operator
            $management->editSingleOrMultipleUsers($browser, [
                'role' => 'operator'
            ]);
            $management->assertEditModal($browser, [
                'missing_countries' => $missing_countries,
                'expected_countries' => $expected_countries,
                'country' => $country,
                'missing_roles' => $missing_roles,
                'expected_roles' => $expected_roles,
                'role' => 'operator'
            ]);
        });
    }

    public function test_admin_block_enable_single_user()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;
                        
            $poc = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'PoC',
                    'country' => Country::first()->name
                ]
            ]);

            $users = User::getUsers();

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@users'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $datatable_data = $management->getDataTableData($users);
            $management->assertDataTable($browser, $datatable_data);
            $management->clickEnableBlockUser($browser, $poc->id);
            $datatable_data[$poc->id]['status_text'] = 'Blocked';
            $management->assertDataTable($browser, $datatable_data);
            $management->clickEditUser($browser, $poc->id);
            $management->assertEditModal($browser, [
                'title' => 'Edit User ' . $poc->name,
                'status_switch' => 'enabled',
                'status_text' => 'Blocked'
            ]);
            // Enable
            $management->editSingleOrMultipleUsers($browser, [
                'enable_block',
                'action' => 'save'
            ]);
            $datatable_data[$poc->id]['status_text'] = 'Enabled';
            $management->assertDataTable($browser, $datatable_data);
        });
    }

    public function test_admin_block_enable_multiple_users()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;

            $country = Country::first()->name;
            
            $poc = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'PoC',
                    'country' => $country
                ]
            ]);

            $operator = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'operator',
                    'country' => $country
                ]
            ]);

            $users = User::getUsers();

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@users'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $datatable_data = $management->getDataTableData($users);
            $management->assertDataTable($browser, $datatable_data);
            $management->selectUsers($browser, [$poc->id, $operator->id]);
            $management->clickEditSelectedUsers($browser);
            $management->assertEditModal($browser, [
                'title' => 'Edit Users',
                'status_switch' => 'enabled',
                'status_text' => 'Enabled'
            ]);
            // Block
            $management->editSingleOrMultipleUsers($browser, [
                'enable_block',
                'action' => 'save'
            ]);
            $datatable_data[$poc->id]['status_text'] = $datatable_data[$operator->id]['status_text'] = 'Blocked';
            $management->assertDataTable($browser, $datatable_data);
            $management->selectUsers($browser, [$poc->id, $operator->id]);
            $management->clickEditSelectedUsers($browser);
            $management->assertEditModal($browser, [
                'title' => 'Edit Users',
                'status_switch' => 'enabled',
                'status_text' => 'Enabled'
            ]);
            // Enable
            $management->editSingleOrMultipleUsers($browser, [
                'action' => 'save'
            ]);
            $datatable_data[$poc->id]['status_text'] = $datatable_data[$operator->id]['status_text'] = 'Enabled';
            $management->assertDataTable($browser, $datatable_data);
        });
    }

    public function test_admin_delete_single_user()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;
            
            $poc = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'PoC',
                    'country' => Country::first()->name
                ]
            ]);
            
            $users = User::getUsers();

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@users'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $datatable_data = $management->getDataTableData($users);
            $management->assertDataTable($browser, $datatable_data);
            $management->clickDeleteUser($browser, $poc->id);
            $management->assertDeleteModal($browser, [
                'title' => 'Delete User',
                'text' => "User '" . $poc->name . "' will be deleted. Are you sure?",
                'actions' => true
            ]);
            $management->deleteSingleOrMultipleUsers($browser, [
                'action' => 'delete'
            ]);
            $datatable_data[$poc->id]['deleted'] = true;
            $management->assertDataTable($browser, $datatable_data);
        });
    }

    public function test_admin_delete_multiple_users()
    {
        $this->browse(function (Browser $browser) {
            $user = $browser->loginAsRole('admin')->user;

            $country = Country::first()->name;

            $poc = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'PoC',
                    'country' => $country
                ]
            ]);

            $operator = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'operator',
                    'country' => $country
                ]
            ]);

            $users = User::getUsers();
            
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@users'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $datatable_data = $management->getDataTableData($users);
            $management->assertDataTable($browser, $datatable_data);
            $management->selectUsers($browser, [$poc->id, $operator->id]);
            $management->clickDeleteSelectedUsers($browser);
            $management->assertDeleteModal($browser, [
                'title' => 'Delete Users',
                'text' => "Selected users will be deleted. Are you sure?",
                'actions' => true
            ]);
            $management->deleteSingleOrMultipleUsers($browser, [
                'action' => 'delete'
            ]);
            $datatable_data[$poc->id]['deleted'] = true;
            $datatable_data[$operator->id]['deleted'] = true;
            $management->assertDataTable($browser, $datatable_data);
        });
    }
}
