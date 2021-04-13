<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Messenger as MessengerModel;

class FeatureTestCase extends MessengerTestCase
{
    protected MessengerProvider $tippin;
    protected MessengerProvider $doe;
    protected MessengerProvider $developers;

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
        $this->tippin = $this->getModelUser()::create([
            'name' => 'Richard Tippin',
            'email' => 'tippindev@gmail.com',
            'password' => 'secret',
        ]);
        $this->doe = $this->getModelUser()::create([
            'name' => 'John Doe',
            'email' => 'doe@example.net',
            'password' => 'secret',
        ]);
        MessengerModel::factory()->owner($this->tippin)->create();
        MessengerModel::factory()->owner($this->doe)->create();
    }

    private function storeBaseCompanies(): void
    {
        $this->developers = $this->getModelCompany()::create([
            'company_name' => 'Developers',
            'company_email' => 'developers@example.net',
            'password' => 'secret',
        ]);
        MessengerModel::factory()->owner($this->developers)->create();
    }
}
