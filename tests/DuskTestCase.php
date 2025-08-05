<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh');
        $this->artisan('db:seed --class=DuskDatabaseSeeder');
    }
    
    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     */
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver();
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $headless = env('DUSK_HEADLESS_MODE', true);
        $arguments = [
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--ignore-ssl-errors',
            '--ignore-certificate-errors',
            '--whitelisted-ips=""',
            '--lang=en-GB'
        ];

        if ($headless) {
            $arguments = array_merge($arguments, [
                '--headless',
                '--window-size=1920, 1080'
            ]);
        }
        else {
            $arguments = array_merge($arguments, [
                '--start-maximized'
            ]);
        }

        $options = new ChromeOptions();
        $options->addArguments($arguments);

        return RemoteWebDriver::create(
            'http://host.docker.internal:4444/wd/hub',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
