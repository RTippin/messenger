<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;

/**
 * Laravel Messenger System, Created by: Richard Tippin.
 * @link https://github.com/RTippin/messenger
 * @link https://github.com/RTippin/messenger-bots
 * @link https://github.com/RTippin/messenger-faker
 * @link https://github.com/RTippin/messenger-ui
 */
class MessengerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register all provider models you wish to use in messenger.
        Messenger::registerProviders([
            // User::class,
        ]);

        // Set the video call driver of your choosing.
        // Messenger::setVideoDriver(MyVideoBroker::class);

        // Register bot handlers you wish to use. You can install the messenger-bots addon for ready-made handlers.
        MessengerBots::registerHandlers([
            //
        ]);

        // Register the packaged bots you wish to use.
        MessengerBots::registerPackagedBots([
            //
        ]);
    }
}
