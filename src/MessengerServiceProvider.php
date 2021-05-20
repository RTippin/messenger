<?php

namespace RTippin\Messenger;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use LogicException;
use RTippin\Messenger\Brokers\FriendBroker;
use RTippin\Messenger\Commands\CallsActivityCheckCommand;
use RTippin\Messenger\Commands\CallsDownCommand;
use RTippin\Messenger\Commands\CallsUpCommand;
use RTippin\Messenger\Commands\InvitesCheckCommand;
use RTippin\Messenger\Commands\ProvidersCacheCommand;
use RTippin\Messenger\Commands\ProvidersClearCommand;
use RTippin\Messenger\Commands\PublishCommand;
use RTippin\Messenger\Commands\PurgeAudioCommand;
use RTippin\Messenger\Commands\PurgeDocumentsCommand;
use RTippin\Messenger\Commands\PurgeImagesCommand;
use RTippin\Messenger\Commands\PurgeMessagesCommand;
use RTippin\Messenger\Commands\PurgeThreadsCommand;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Http\Middleware\MessengerApi;
use RTippin\Messenger\Services\EmojiService;

class MessengerServiceProvider extends ServiceProvider
{
    use ChannelMap;
    use EventMap;
    use PolicyMap;
    use RouteMap;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->registerRouterServices();
        $this->registerPolicies();
        $this->registerListeners();
        $this->registerChannels();

        Collection::macro('forProvider', function (MessengerProvider $provider, string $morph = 'owner') {
            /** @var Collection $this */
            return $this->where("{$morph}_id", '=', $provider->getKey())
                ->where("{$morph}_type", '=', $provider->getMorphClass());
        });

        if (config('messenger.routing.web.enabled')) {
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'messenger');
        }

        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    private function bootForConsole(): void
    {
        $this->commands([
            CallsActivityCheckCommand::class,
            CallsDownCommand::class,
            CallsUpCommand::class,
            InvitesCheckCommand::class,
            ProvidersCacheCommand::class,
            ProvidersClearCommand::class,
            PublishCommand::class,
            PurgeAudioCommand::class,
            PurgeDocumentsCommand::class,
            PurgeImagesCommand::class,
            PurgeMessagesCommand::class,
            PurgeThreadsCommand::class,
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/messenger.php' => config_path('messenger.php'),
        ], 'messenger.config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'messenger.migrations');

        // Only load our views and assets if web routes are enabled.
        if (config('messenger.routing.web.enabled')) {
            $this->publishes([
                __DIR__.'/../resources/views' => base_path('resources/views/vendor/messenger'),
            ], 'messenger.views');

            $this->publishes([
                __DIR__.'/../public' => public_path('vendor/messenger'),
            ], 'messenger.assets');
        }

        if (config('messenger.calling.driver') === 'janus') {
            $this->publishes([
                __DIR__.'/../config/janus.php' => config_path('janus.php'),
            ], 'messenger.janus.config');
        }
    }

    /**
     * Register Messenger's services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/messenger.php', 'messenger');

        $this->app->singleton(Messenger::class, Messenger::class);

        $this->app->alias(Messenger::class, 'messenger');

        $this->app->singleton(FriendDriver::class, FriendBroker::class);

        $this->app->singleton(EmojiInterface::class, EmojiService::class);

        $this->app->bind(BroadcastDriver::class, $this->getBroadcastImplementation());

        $this->app->bind(VideoDriver::class, $this->getVideoImplementation());
    }

    /**
     * Prepend our API middleware, merge additional
     * middleware, append throttle middleware.
     *
     * @param $middleware
     * @return array
     */
    private function mergeApiMiddleware($middleware): array
    {
        $merged = array_merge([MessengerApi::class], is_array($middleware) ? $middleware : [$middleware]);

        array_push($merged, 'throttle:messenger-api');

        return $merged;
    }

    /**
     * Get the driver set in config for our services broadcasting feature.
     *
     * @return string
     */
    private function getBroadcastImplementation(): string
    {
        $broadcastDrivers = config('messenger.drivers.broadcasting');
        $alias = config('messenger.broadcasting.driver') ?? 'null';

        if (! array_key_exists($alias, $broadcastDrivers)) {
            $this->throwDriverNotExist($alias);
        }

        return $broadcastDrivers[$alias];
    }

    /**
     * Get the driver set in config for our services video feature.
     *
     * @return string
     * @throws LogicException
     */
    private function getVideoImplementation(): string
    {
        $videoDrivers = config('messenger.drivers.calling');
        $alias = config('messenger.calling.driver') ?? 'null';

        if (! array_key_exists($alias, $videoDrivers)) {
            $this->throwDriverNotExist($alias);
        }

        return $videoDrivers[$alias];
    }

    /**
     * @param string $driverName
     * @throws LogicException
     */
    private function throwDriverNotExist(string $driverName)
    {
        throw new LogicException("The $driverName driver does not exist.");
    }
}
