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
use RTippin\Messenger\MessageTypes\Audio;
use RTippin\Messenger\MessageTypes\Document;
use RTippin\Messenger\MessageTypes\Image;
use RTippin\Messenger\MessageTypes\Message;
use RTippin\Messenger\MessageTypes\System;
use RTippin\Messenger\MessageTypes\Video;
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
        $this->mergeConfigFrom(__DIR__.'/../config/messenger.php', 'messenger');

        $this->app->singleton(Messenger::class, Messenger::class);
        $this->app->singleton(MessengerTypes::class, MessengerTypes::class);
        $this->app->singleton(MessengerBots::class, MessengerBots::class);
        $this->app->singleton(FriendDriver::class, FriendBroker::class);
        $this->app->singleton(EmojiInterface::class, EmojiService::class);
        $this->app->bind(MessengerComposer::class, MessengerComposer::class);
        $this->app->bind(BroadcastDriver::class, BroadcastBroker::class);
        $this->app->bind(VideoDriver::class, NullVideoBroker::class);
        $this->app->extend(ExceptionHandler::class, fn (ExceptionHandler $handler) => new Handler($handler));
        $this->app->alias(Messenger::class, 'messenger');
        $this->app->alias(MessengerTypes::class, 'messenger-types');
        $this->app->alias(MessengerBots::class, 'messenger-bots');
        $this->app->alias(MessengerComposer::class, 'messenger-composer');
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
        $this->registerMessageTypes();

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

        $this->publishes([
            __DIR__.'/../config/messenger.php' => config_path('messenger.php'),
        ], 'messenger.config');

        $this->publishes([
            __DIR__.'/../stubs/MessengerServiceProvider.stub' => app_path('Providers/MessengerServiceProvider.php'),
        ], 'messenger.provider');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'messenger.migrations');
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

    private function registerMessageTypes(): void
    {
        $types = $this->app->make(MessengerTypes::class);

        $types->registerProviders([
            Message::class,
            Document::class,
            Image::class,
            Audio::class,
            Video::class,
            new System(88, 'PARTICIPANT_JOINED_WITH_INVITE'),
            new System(90, 'VIDEO_CALL'),
            new System(91, 'GROUP_AVATAR_CHANGED'),
            new System(92, 'THREAD_ARCHIVED'),
            new System(93, 'GROUP_CREATED'),
            new System(94, 'GROUP_RENAMED'),
            new System(95, 'DEMOTED_ADMIN'),
            new System(96, 'PROMOTED_ADMIN'),
            new System(97, 'PARTICIPANT_LEFT_GROUP'),
            new System(98, 'PARTICIPANT_REMOVED'),
            new System(99, 'PARTICIPANTS_ADDED'),
            new System(100, 'BOT_ADDED'),
            new System(101, 'BOT_RENAMED'),
            new System(102, 'BOT_AVATAR_CHANGED'),
            new System(103, 'BOT_REMOVED'),
            new System(104, 'BOT_PACKAGE_INSTALLED'),
        ]);
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
