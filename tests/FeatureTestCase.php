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

    /**
     * Set TRUE to run all feature test with
     * provider models/tables using UUIDS.
     */
    const UseUUID = false;

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

        $config->set('messenger.provider_uuids', self::UseUUID);

        $config->set('messenger.calling.enabled', true);

        $config->set('messenger.providers', [
            'user' => [
                'model' => (self::UseUUID ? UserModelUuid::class : UserModel::class),
                'searchable' => true,
                'friendable' => true,
                'devices' => true,
                'default_avatar' => public_path('vendor/messenger/images/users.png'),
                'provider_interactions' => [
                    'can_message' => true,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
            'company' => [
                'model' => (self::UseUUID ? CompanyModelUuid::class : CompanyModel::class),
                'searchable' => true,
                'friendable' => true,
                'devices' => true,
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

        if (self::UseUUID) {
            Messenger::getProviderMessenger(UserModelUuid::create($tippin));

            Messenger::getProviderMessenger(UserModelUuid::create($doe));
        } else {
            Messenger::getProviderMessenger(UserModel::create($tippin));

            Messenger::getProviderMessenger(UserModel::create($doe));
        }
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

        if (self::UseUUID) {
            Messenger::getProviderMessenger(CompanyModelUuid::create($developers));

            Messenger::getProviderMessenger(CompanyModelUuid::create($laravel));
        } else {
            Messenger::getProviderMessenger(CompanyModel::create($developers));

            Messenger::getProviderMessenger(CompanyModel::create($laravel));
        }
    }
}
