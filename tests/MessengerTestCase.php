<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\Messenger\Support\BotActionHandler;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotPackage;
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
     * Set TRUE to log both HTTP responses
     * to our generates json file.
     */
    protected bool $withHttpLogging = false;

    /**
     * Set TRUE to log broadcasting payloads
     * to our generates json files.
     */
    protected bool $withBroadcastLogging = false;

    /**
     * @param  Application  $app
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            MessengerServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
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

        if (env('HTTP_LOGGING') === true) {
            $this->withHttpLogging = true;
        }

        if (env('BROADCAST_LOGGING') === true) {
            $this->withBroadcastLogging = true;
        }

        $config->set('messenger.provider_uuids', $this->useUUID);
        $config->set('messenger.calling.enabled', true);
        $config->set('messenger.bots.enabled', true);
        $config->set('messenger.storage.avatars.disk', 'public');
        $config->set('messenger.storage.threads.disk', 'messenger');
        $config->set('messenger.files.default_not_found_image', __DIR__.'/Fixtures/404.png');
        $config->set('messenger.files.default_ghost_avatar', __DIR__.'/Fixtures/404.png');
        $config->set('messenger.files.default_thread_avatar', __DIR__.'/Fixtures/404.png');
        $config->set('messenger.files.default_bot_avatar', __DIR__.'/Fixtures/404.png');

        if ($this->useMorphMap) {
            Relation::morphMap([
                'users' => UserModel::class,
                'companies' => CompanyModel::class,
            ]);
        }
    }

    /**
     * Register our providers.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::registerProviders([
            UserModel::class,
            CompanyModel::class,
        ]);
    }

    /**
     * Reset the static properties we dynamically set on the classes.
     */
    protected function tearDown(): void
    {
        UserModel::reset();
        CompanyModel::reset();
        SillyBotHandler::reset();
        SillyBotPackage::reset();
        BotActionHandler::isTesting(false);

        parent::tearDown();
    }
}
