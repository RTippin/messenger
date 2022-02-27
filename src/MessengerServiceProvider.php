<?php

namespace RTippin\Messenger;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Brokers\BroadcastBroker;
use RTippin\Messenger\Brokers\FriendBroker;
use RTippin\Messenger\Brokers\NullVideoBroker;
use RTippin\Messenger\Commands\AttachMessengersCommand;
use RTippin\Messenger\Commands\CallsActivityCheckCommand;
use RTippin\Messenger\Commands\CallsDownCommand;
use RTippin\Messenger\Commands\CallsUpCommand;
use RTippin\Messenger\Commands\InstallCommand;
use RTippin\Messenger\Commands\InvitesCheckCommand;
use RTippin\Messenger\Commands\MakeBotCommand;
use RTippin\Messenger\Commands\MakePackagedBotCommand;
use RTippin\Messenger\Commands\PurgeAudioCommand;
use RTippin\Messenger\Commands\PurgeBotsCommand;
use RTippin\Messenger\Commands\PurgeDocumentsCommand;
use RTippin\Messenger\Commands\PurgeImagesCommand;
use RTippin\Messenger\Commands\PurgeMessagesCommand;
use RTippin\Messenger\Commands\PurgeThreadsCommand;
use RTippin\Messenger\Commands\PurgeVideosCommand;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Exceptions\Handler;
use RTippin\Messenger\Http\Middleware\MessengerApi;
use RTippin\Messenger\Listeners\BotSubscriber;
use RTippin\Messenger\Listeners\CallSubscriber;
use RTippin\Messenger\Listeners\SystemMessageSubscriber;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Services\EmojiService;
use RTippin\Messenger\Support\MessengerComposer;

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
        $this->mergeConfigFrom(
            path: __DIR__.'/../config/messenger.php',
            key: 'messenger'
        );

        $this->app->singleton(
            abstract: Messenger::class,
            concrete: Messenger::class
        );

        $this->app->singleton(
            abstract: MessengerBots::class,
            concrete: MessengerBots::class
        );

        $this->app->singleton(
            abstract: FriendDriver::class,
            concrete: FriendBroker::class
        );

        $this->app->singleton(
            abstract: EmojiInterface::class,
            concrete: EmojiService::class
        );

        $this->app->bind(
            abstract: MessengerComposer::class,
            concrete: MessengerComposer::class
        );

        $this->app->bind(
            abstract: BroadcastDriver::class,
            concrete: BroadcastBroker::class
        );

        $this->app->bind(
            abstract: VideoDriver::class,
            concrete: NullVideoBroker::class
        );

        $this->app->extend(
            abstract: ExceptionHandler::class,
            closure: fn (ExceptionHandler $handler): Handler => new Handler($handler)
        );

        $this->app->alias(
            abstract: Messenger::class,
            alias:  'messenger'
        );

        $this->app->alias(
            abstract: MessengerBots::class,
            alias: 'messenger-bots'
        );

        $this->app->alias(
            abstract: MessengerComposer::class,
            alias: 'messenger-composer'
        );
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        Messenger::shouldUseUuids(config('messenger.provider_uuids'));

        Messenger::shouldUseAbsoluteRoutes(config('messenger.use_absolute_routes'));

        Relation::morphMap([
            'bots' => Bot::class,
        ]);

        $this->registerRouterServices();
        $this->registerPolicies();
        $this->registerSubscribers();
        $this->registerChannels();

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
            AttachMessengersCommand::class,
            CallsActivityCheckCommand::class,
            CallsDownCommand::class,
            CallsUpCommand::class,
            InstallCommand::class,
            InvitesCheckCommand::class,
            MakeBotCommand::class,
            MakePackagedBotCommand::class,
            PurgeAudioCommand::class,
            PurgeBotsCommand::class,
            PurgeDocumentsCommand::class,
            PurgeImagesCommand::class,
            PurgeMessagesCommand::class,
            PurgeThreadsCommand::class,
            PurgeVideosCommand::class,
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes(
            paths: [__DIR__.'/../config/messenger.php' => config_path('messenger.php')],
            groups: 'messenger.config'
        );

        $this->publishes(
            paths: [__DIR__.'/../stubs/MessengerServiceProvider.stub' => app_path('Providers/MessengerServiceProvider.php')],
            groups: 'messenger.provider'
        );

        $this->publishes(
            paths: [__DIR__.'/../database/migrations' => database_path('migrations')],
            groups: 'messenger.migrations'
        );
    }

    /**
     * Register the Event Subscribers.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    private function registerSubscribers(): void
    {
        $events = $this->app->make(Dispatcher::class);

        $events->subscribe(CallSubscriber::class);
        $events->subscribe(SystemMessageSubscriber::class);
        $events->subscribe(BotSubscriber::class);
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

        $merged[] = 'throttle:messenger-api';

        return $merged;
    }
}
