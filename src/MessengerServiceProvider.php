<?php

namespace RTippin\Messenger;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Brokers\BroadcastBroker;
use RTippin\Messenger\Brokers\FriendBroker;
use RTippin\Messenger\Commands\CallsActivityCheckCommand;
use RTippin\Messenger\Commands\CallsDownCommand;
use RTippin\Messenger\Commands\CallsUpCommand;
use RTippin\Messenger\Commands\InvitesCheckCommand;
use RTippin\Messenger\Commands\ProvidersCacheCommand;
use RTippin\Messenger\Commands\ProvidersClearCommand;
use RTippin\Messenger\Commands\PublishCommand;
use RTippin\Messenger\Commands\PurgeAudioCommand;
use RTippin\Messenger\Commands\PurgeBotsCommand;
use RTippin\Messenger\Commands\PurgeDocumentsCommand;
use RTippin\Messenger\Commands\PurgeImagesCommand;
use RTippin\Messenger\Commands\PurgeMessagesCommand;
use RTippin\Messenger\Commands\PurgeThreadsCommand;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Http\Middleware\MessengerApi;
use RTippin\Messenger\Listeners\BotSubscriber;
use RTippin\Messenger\Listeners\CallSubscriber;
use RTippin\Messenger\Listeners\SystemMessageSubscriber;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Services\EmojiService;

class MessengerServiceProvider extends ServiceProvider
{
    use ChannelMap,
        PolicyMap,
        RouteMap;

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

        $this->app->singleton(MessengerBots::class, MessengerBots::class);

        $this->app->alias(MessengerBots::class, 'messenger-bots');

        $this->app->singleton(FriendDriver::class, FriendBroker::class);

        $this->app->singleton(EmojiInterface::class, EmojiService::class);

        $this->app->bind(BroadcastDriver::class, BroadcastBroker::class);
    }

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
        $this->registerSubscribers();
        $this->registerChannels();

        Relation::morphMap([
            'bots' => Bot::class,
        ]);

        Collection::macro('forProvider', function (MessengerProvider $provider, string $morph = 'owner'): Collection {
            /** @var Collection $this */
            return $this->where("{$morph}_id", '=', $provider->getKey())
                ->where("{$morph}_type", '=', $provider->getMorphClass());
        });

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
            PurgeBotsCommand::class,
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
            __DIR__.'/../config/janus.php' => config_path('janus.php'),
        ], 'messenger.janus.config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'messenger.migrations');
    }

    /**
     * Register the Event Subscribers.
     *
     * @return void
     * @throws BindingResolutionException
     */
    private function registerSubscribers()
    {
        $events = $this->app->make(Dispatcher::class);

        if (config('messenger.calling.subscriber.enabled')) {
            $events->subscribe(CallSubscriber::class);
        }

        if (config('messenger.system_messages.subscriber.enabled')) {
            $events->subscribe(SystemMessageSubscriber::class);
        }

        if (config('messenger.bots.subscriber.enabled')) {
            $events->subscribe(BotSubscriber::class);
        }
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
}
