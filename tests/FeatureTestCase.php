<?php

namespace RTippin\Messenger\Tests;

use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\Messenger\Tests\stubs\CompanyModel;
use RTippin\Messenger\Tests\stubs\CompanyModelUuid;
use RTippin\Messenger\Tests\stubs\UserModel;
use RTippin\Messenger\Tests\stubs\UserModelUuid;

class FeatureTestCase extends TestCase
{
    use HelperTrait;

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

        $config->set('messenger.provider_uuids', false);

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
        if (config('messenger.provider_uuids')) {
            $userOne = UserModelUuid::create([
                'name' => 'Richard Tippin',
                'email' => 'richard.tippin@gmail.com',
                'password' => 'secret',
            ]);

            Messenger::getProviderMessenger($userOne);

            $userTwo = UserModelUuid::create([
                'name' => 'John Doe',
                'email' => 'doe@example.net',
                'password' => 'secret',
            ]);

            Messenger::getProviderMessenger($userTwo);
        } else {
            $userOne = UserModel::create([
                'name' => 'Richard Tippin',
                'email' => 'richard.tippin@gmail.com',
                'password' => 'secret',
            ]);

            Messenger::getProviderMessenger($userOne);

            $userTwo = UserModel::create([
                'name' => 'John Doe',
                'email' => 'doe@example.net',
                'password' => 'secret',
            ]);

            Messenger::getProviderMessenger($userTwo);
        }
    }

    private function storeBaseCompanies(): void
    {
        if (config('messenger.provider_uuids')) {
            $companyOne = CompanyModelUuid::create([
                'company_name' => 'Developers',
                'company_email' => 'developers@example.net',
                'password' => 'secret',
            ]);

            Messenger::getProviderMessenger($companyOne);

            $companyTwo = CompanyModelUuid::create([
                'company_name' => 'Laravel',
                'company_email' => 'laravel@example.net',
                'password' => 'secret',
            ]);

            Messenger::getProviderMessenger($companyTwo);
        } else {
            $companyOne = CompanyModel::create([
                'company_name' => 'Developers',
                'company_email' => 'developers@example.net',
                'password' => 'secret',
            ]);

            Messenger::getProviderMessenger($companyOne);

            $companyTwo = CompanyModel::create([
                'company_name' => 'Laravel',
                'company_email' => 'laravel@example.net',
                'password' => 'secret',
            ]);

            Messenger::getProviderMessenger($companyTwo);
        }
    }
}
