<?php

namespace RTippin\Messenger\Tests;

use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\Messenger\Tests\stubs\CompanyModel;
use RTippin\Messenger\Tests\stubs\CompanyModelUuid;
use RTippin\Messenger\Tests\stubs\UserModel;
use RTippin\Messenger\Tests\stubs\UserModelUuid;

class MessengerTestCase extends TestCase
{
    use HelperTrait;

    /**
     * Set TRUE to run all feature test with
     * provider models/tables using UUIDS.
     */
    const UseUUID = false;

    protected function tearDown(): void
    {
        Messenger::reset();

        parent::tearDown();
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

        $config->set('messenger.provider_uuids', self::UseUUID);

        $config->set('messenger.calling.enabled', true);

        $config->set('messenger.providers', $this->getBaseProvidersConfig());

        $config->set('messenger.site_name', 'Messenger-Testbench');
    }

    protected function getBaseProvidersConfig(): array
    {
        return [
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
        ];
    }
}
