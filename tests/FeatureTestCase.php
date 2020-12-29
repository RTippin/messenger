<?php

namespace RTippin\Messenger\Tests;

use Orchestra\Testbench\TestCase;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\Messenger\Models\Messenger;
use RTippin\Messenger\Tests\stubs\CompanyModel;
use RTippin\Messenger\Tests\stubs\UserModel;

class FeatureTestCase extends TestCase
{
    use HelperTrait;

    const UserModelType = 'RTippin\Messenger\Tests\stubs\UserModel';

    const CompanyModelType = 'RTippin\Messenger\Tests\stubs\CompanyModel';

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

    protected function getPackageProviders($app): array
    {
        return [
            MessengerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $config = $app->get('config');

        $config->set('database.default', 'testbench');

        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $config->set('messenger.providers', [
            'user' => [
                'model' => UserModel::class,
                'searchable' => true,
                'friendable' => true,
                'devices' => false,
                'default_avatar' => public_path('vendor/messenger/images/users.png'),
                'provider_interactions' => [
                    'can_message' => true,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
            'company' => [
                'model' => CompanyModel::class,
                'searchable' => true,
                'friendable' => true,
                'devices' => false,
                'default_avatar' => public_path('vendor/messenger/images/company.png'),
                'provider_interactions' => [
                    'can_message' => true,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
        ]);

        $config->set('messenger.site_name', 'Messenger-Testbench');
    }

    private function storeBaseUsers(): void
    {
        $userOne = UserModel::create([
            'name' => 'Richard Tippin',
            'email' => 'richard.tippin@gmail.com',
            'password' => 'secret',
        ]);

        Messenger::create([
            'owner_id' => $userOne->getKey(),
            'owner_type' => self::UserModelType,
        ]);

        $userTwo = UserModel::create([
            'name' => 'John Doe',
            'email' => 'doe@example.net',
            'password' => 'secret',
        ]);

        Messenger::create([
            'owner_id' => $userTwo->getKey(),
            'owner_type' => self::UserModelType,
        ]);
    }

    private function storeBaseCompanies(): void
    {
        $companyOne = CompanyModel::create([
            'company_name' => 'Developers',
            'company_email' => 'developers@example.net',
            'password' => 'secret',
        ]);

        Messenger::create([
            'owner_id' => $companyOne->getKey(),
            'owner_type' => self::CompanyModelType,
        ]);

        $companyTwo = CompanyModel::create([
            'company_name' => 'Laravel',
            'company_email' => 'laravel@example.net',
            'password' => 'secret',
        ]);

        Messenger::create([
            'owner_id' => $companyTwo->getKey(),
            'owner_type' => self::CompanyModelType,
        ]);
    }
}
