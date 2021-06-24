<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\CompanyModelUuid;
use RTippin\Messenger\Tests\Fixtures\UserModel;
use RTippin\Messenger\Tests\Fixtures\UserModelUuid;

class MessengerTestCase extends TestCase
{
    use HelperTrait;

    /**
     * Set TRUE to run all feature test with
     * provider models/tables using UUIDS.
     */
    const UseUUID = false;

    /**
     * Set TRUE to run all feature test with
     * relation morph map set for providers.
     */
    const UseMorphMap = false;

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
        $config->set('messenger.bots.enabled', true);
        $config->set('messenger.storage.avatars.disk', 'public');
        $config->set('messenger.storage.threads.disk', 'messenger');
        $config->set('messenger.providers', $this->getBaseProvidersConfig());

        if (self::UseMorphMap) {
            Relation::morphMap([
                'users' => $this->getModelUser(),
                'companies' => $this->getModelCompany(),
            ]);
        }
    }

    protected function getBaseProvidersConfig(): array
    {
        return [
            'user' => [
                'model' => $this->getModelUser(),
                'searchable' => true,
                'friendable' => true,
                'devices' => true,
                'default_avatar' => '/path/to/user.png',
                'provider_interactions' => [
                    'can_message' => true,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
            'company' => [
                'model' => $this->getModelCompany(),
                'searchable' => true,
                'friendable' => true,
                'devices' => true,
                'default_avatar' => '/path/to/company.png',
                'provider_interactions' => [
                    'can_message' => true,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
        ];
    }

    /**
     * @return MessengerProvider|UserModel|UserModelUuid|string
     */
    protected function getModelUser(): string
    {
        return self::UseUUID ? UserModelUuid::class : UserModel::class;
    }

    /**
     * @return MessengerProvider|CompanyModel|CompanyModelUuid|string
     */
    protected function getModelCompany(): string
    {
        return self::UseUUID ? CompanyModelUuid::class : CompanyModel::class;
    }
}
