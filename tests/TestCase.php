<?php

namespace Tests;

use App\HelperFunctions\EcasTestHelper;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $seed = true;
    protected $seeder = TestDatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        EcasTestHelper::logoutEcasUser();
    }

    /**
     * Make ajax POST request
     */
    protected function ajaxPost($user, $url, array $data = [])
    {
        return $this->actingAs($user)->post($url, $data, array('HTTP_X-Requested-With' => 'XMLHttpRequest'));
    }
}
