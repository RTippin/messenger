<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Models\Messenger as MessengerModel;

class FeatureTestCase extends MessengerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
        $this->artisan('migrate', [
            '--database' => 'testbench',
        ])->run();
        $this->storeBaseUsers();
        $this->storeBaseCompanies();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $config = $app->get('config');

        $config->set('database.default', 'testbench');
        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    private function storeBaseUsers(): void
    {
        $tippin = $this->getModelUser()::create([
            'name' => 'Richard Tippin',
            'email' => 'tippindev@gmail.com',
            'password' => 'secret',
        ]);
        $doe = $this->getModelUser()::create([
            'name' => 'John Doe',
            'email' => 'doe@example.net',
            'password' => 'secret',
        ]);
        MessengerModel::factory()->provider($tippin)->create();
        MessengerModel::factory()->provider($doe)->create();
    }

    private function storeBaseCompanies(): void
    {
        $developers = $this->getModelCompany()::create([
            'company_name' => 'Developers',
            'company_email' => 'developers@example.net',
            'password' => 'secret',
        ]);
        MessengerModel::factory()->provider($developers)->create();
    }
}
