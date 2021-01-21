<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\stubs\CompanyModel;
use RTippin\Messenger\Tests\stubs\CompanyModelUuid;
use RTippin\Messenger\Tests\stubs\UserModel;
use RTippin\Messenger\Tests\stubs\UserModelUuid;

class FeatureTestCase extends MessengerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/stubs/migrations');

        $this->artisan('migrate', [
            '--database' => 'testbench',
        ])->run();

        $this->storeBaseUsers();

        $this->storeBaseCompanies();
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
        $tippin = [
            'name' => 'Richard Tippin',
            'email' => 'richard.tippin@gmail.com',
            'password' => 'secret',
        ];

        $doe = [
            'name' => 'John Doe',
            'email' => 'doe@example.net',
            'password' => 'secret',
        ];

        /** @var UserModelUuid|UserModel $model */
        $model = self::UseUUID ? UserModelUuid::class : UserModel::class;

        Messenger::getProviderMessenger($model::create($tippin));

        Messenger::getProviderMessenger($model::create($doe));
    }

    private function storeBaseCompanies(): void
    {
        $developers = [
            'company_name' => 'Developers',
            'company_email' => 'developers@example.net',
            'password' => 'secret',
        ];

        $laravel = [
            'company_name' => 'Laravel',
            'company_email' => 'laravel@example.net',
            'password' => 'secret',
        ];

        /** @var CompanyModelUuid|CompanyModel $model */
        $model = self::UseUUID ? CompanyModelUuid::class : CompanyModel::class;

        Messenger::getProviderMessenger($model::create($developers));

        Messenger::getProviderMessenger($model::create($laravel));
    }
}
