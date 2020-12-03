<?php

namespace RTippin\Messenger;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Brokers\FriendBroker;
use RTippin\Messenger\Commands\CallsActivityCheck;
use RTippin\Messenger\Commands\InvitesCheck;
use RTippin\Messenger\Commands\ProvidersCache;
use RTippin\Messenger\Commands\ProvidersClear;
use RTippin\Messenger\Commands\PurgeDocuments;
use RTippin\Messenger\Commands\PurgeImages;
use RTippin\Messenger\Commands\PurgeMessages;
use RTippin\Messenger\Commands\PurgeThreads;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Contracts\PushNotificationDriver;
use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Http\Middleware\SetMessengerProvider;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Policies\CallParticipantPolicy;
use RTippin\Messenger\Policies\CallPolicy;
use RTippin\Messenger\Policies\FriendPolicy;
use RTippin\Messenger\Policies\InvitePolicy;
use RTippin\Messenger\Policies\MessagePolicy;
use RTippin\Messenger\Policies\ParticipantPolicy;
use RTippin\Messenger\Policies\PendingFriendPolicy;
use RTippin\Messenger\Policies\SentFriendPolicy;
use RTippin\Messenger\Policies\ThreadPolicy;

class MessengerServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for messenger models.
     *
     * @var array
     */
    protected array $policies = [
        Call::class => CallPolicy::class,
        CallParticipant::class => CallParticipantPolicy::class,
        Thread::class => ThreadPolicy::class,
        Participant::class => ParticipantPolicy::class,
        Message::class => MessagePolicy::class,
        Invite::class => InvitePolicy::class,
        Friend::class => FriendPolicy::class,
        PendingFriend::class => PendingFriendPolicy::class,
        SentFriend::class => SentFriendPolicy::class
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->registerHelpers();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'messenger');

        $this->registerMiddleware();

        $this->registerRoutes();

        if($this->app['config']->get('messenger.routing.api.enabled'))
        {
            $this->registerPolicies();
        }

        if ($this->app->runningInConsole())
        {
            $this->bootForConsole();
        }
    }

    /**
     * @throws BindingResolutionException
     */
    protected function registerMiddleware()
    {
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('messenger.provider', SetMessengerProvider::class);
    }

    /**
     * Register all routes used by messenger
     * @noinspection PhpIncludeInspection
     */
    protected function registerRoutes()
    {
        if($this->app['config']->get('messenger.routing.api.enabled'))
        {
            Route::group($this->apiRouteConfiguration(), function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
            });
        }

        if($this->app['config']->get('messenger.routing.web.enabled'))
        {
            Route::group($this->webRouteConfiguration(), function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

        if($this->app['config']->get('messenger.routing.channels.enabled'))
        {
//            Broadcast::routes($this->channelRouteConfiguration());

            if (file_exists($file = __DIR__.'/../routes/channels.php'))
            {
                require_once $file;
            }
        }
    }

    /**
     * Get the Messenger API route group configuration array.
     *
     * @return array
     */
    protected function apiRouteConfiguration(): array
    {
        return [
            'domain' => $this->app['config']->get('messenger.routing.api.domain'),
            'prefix' => $this->app['config']->get('messenger.routing.api.prefix'),
            'middleware' => $this->app['config']->get('messenger.routing.api.middleware'),
        ];
    }

    /**
     * Get the Messenger API route group configuration array.
     *
     * @return array
     */
    protected function webRouteConfiguration(): array
    {
        return [
            'domain' => $this->app['config']->get('messenger.routing.web.domain'),
            'prefix' => $this->app['config']->get('messenger.routing.web.prefix'),
            'middleware' => $this->app['config']->get('messenger.routing.web.middleware'),
        ];
    }

    /**
     * Get the Broadcasting channel route group configuration array.
     *
     * @return array
     */
    protected function channelRouteConfiguration(): array
    {
        return [
            'domain' => $this->app['config']->get('messenger.routing.channels.domain'),
            'prefix' => $this->app['config']->get('messenger.routing.channels.prefix'),
            'middleware' => $this->app['config']->get('messenger.routing.channels.middleware'),
        ];
    }


    /**
     * Register helpers file
     * @noinspection PhpIncludeInspection
     */
    protected function registerHelpers()
    {
        if (file_exists($file = __DIR__.'/helpers.php'))
        {
            require_once $file;
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/messenger.php', 'messenger');

        $this->app->singleton(
            Messenger::class,
            Messenger::class
        );

        $this->app->alias(
            Messenger::class,
            'messenger'
        );

        $this->app->singleton(
            FriendDriver::class,
            FriendBroker::class
        );

        $this->app->singleton(
            BroadcastDriver::class,
            $this->getBroadcastImplementation()
        );

        $this->app->singleton(
            PushNotificationDriver::class,
            $this->getPushNotificationImplementation()
        );

        $this->app->singleton(
            VideoDriver::class,
            $this->getVideoImplementation()
        );

        $this->app->register(MessengerEventServiceProvider::class);
    }

    /**
     * Get the driver set in config for our services broadcasting feature
     *
     * @return string
     */
    protected function getBroadcastImplementation(): string
    {
        $alias = $this->app['config']->get('messenger.broadcasting.driver');

        return $this->app['config']->get('messenger.drivers.broadcasting')[$alias ?? 'null'];
    }

    /**
     * Get the driver set in config for our services push notifications
     *
     * @return string
     */
    protected function getPushNotificationImplementation(): string
    {
        $alias = $this->app['config']->get('messenger.push_notifications.driver');

        return $this->app['config']->get('messenger.drivers.push_notifications')[$alias ?? 'null'];
    }

    /**
     * Get the driver set in config for our services video feature
     *
     * @return string
     */
    protected function getVideoImplementation(): string
    {
        $alias = $this->app['config']->get('messenger.calling.driver');

        return $this->app['config']->get('messenger.drivers.calling')[$alias ?? 'null'];
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['messenger'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Registering package commands.
        $this->commands([
            CallsActivityCheck::class,
            InvitesCheck::class,
            ProvidersCache::class,
            ProvidersClear::class,
            PurgeDocuments::class,
            PurgeImages::class,
            PurgeMessages::class,
            PurgeThreads::class
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/messenger.php' => config_path('messenger.php'),
        ], 'messenger.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/rtippin'),
        ], 'messenger.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/rtippin'),
        ], 'messenger.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/rtippin'),
        ], 'messenger.views');*/


    }
    /**
     * Register the application's policies.
     *
     * @return void
     */
    protected function registerPolicies()
    {
        foreach ($this->policies() as $key => $value) {
            Gate::policy($key, $value);
        }
    }

    /**
     * Get the policies defined on the provider.
     *
     * @return array
     */
    protected function policies()
    {
        return $this->policies;
    }

}
