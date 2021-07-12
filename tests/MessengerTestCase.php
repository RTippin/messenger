<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use Orchestra\Testbench\TestCase;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;

class MessengerTestCase extends TestCase
{
    /**
     * Set TRUE to run all feature test with
     * provider models/tables using UUIDS.
     */
    protected bool $useUUID = false;

    /**
     * Set TRUE to run all feature test with
     * relation morph map set for providers.
     */
    protected bool $useMorphMap = false;

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            MessengerServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $config = $app->get('config');

        if (env('USE_UUID') === true) {
            $this->useUUID = true;
        }

        if (env('USE_MORPH_MAPS') === true) {
            $this->useMorphMap = true;
        }

        $config->set('messenger.provider_uuids', $this->useUUID);
        $config->set('messenger.calling.enabled', true);
        $config->set('messenger.bots.enabled', true);
        $config->set('messenger.storage.avatars.disk', 'public');
        $config->set('messenger.storage.threads.disk', 'messenger');
        $config->set('messenger.providers', $this->getBaseProvidersConfig());

        if ($this->useMorphMap) {
            Relation::morphMap([
                'users' => UserModel::class,
                'companies' => CompanyModel::class,
            ]);
        }
    }

    /**
     * @return array[]
     */
    protected function getBaseProvidersConfig(): array
    {
        return [
            'user' => [
                'model' => UserModel::class,
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
                'model' => CompanyModel::class,
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
}
