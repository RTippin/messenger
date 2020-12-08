<?php

namespace RTippin\Messenger\Tests;

use Orchestra\Testbench\TestCase;
use RTippin\Messenger\MessengerServiceProvider;

class FeatureTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate',
            ['--database' => 'testbench'])->run();
    }

    protected function getPackageProviders($app): array
    {
        return [
            MessengerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $config = $app->get('config');

        $config->set('database.default', 'testbench');

        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}