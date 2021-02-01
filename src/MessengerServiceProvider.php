<?php

namespace RTippin\Messenger;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Brokers\FriendBroker;
use RTippin\Messenger\Commands\CallsActivityCheck;
use RTippin\Messenger\Commands\InvitesCheck;
use RTippin\Messenger\Commands\ProvidersCache;
use RTippin\Messenger\Commands\ProvidersClear;
use RTippin\Messenger\Commands\Publish;
use RTippin\Messenger\Commands\PurgeDocuments;
use RTippin\Messenger\Commands\PurgeImages;
use RTippin\Messenger\Commands\PurgeMessages;
use RTippin\Messenger\Commands\PurgeThreads;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Http\Middleware\AuthenticateOptional;
use RTippin\Messenger\Http\Middleware\MessengerApi;
use RTippin\Messenger\Http\Middleware\SetMessengerProvider;

class MessengerServiceProvider extends ServiceProvider
{
    use ChannelMap;
    use EventMap;
    use PolicyMap;
    use RouteMap;

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['messenger'];
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->registerMiddleware();
        $this->configureRateLimiting();
        $this->registerPolicies();
        $this->registerEvents();
        $this->registerRoutes();
        $this->registerChannels();
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'messenger');

        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        $this->commands([
            CallsActivityCheck::class,
            InvitesCheck::class,
            ProvidersCache::class,
            ProvidersClear::class,
            Publish::class,
            PurgeDocuments::class,
            PurgeImages::class,
            PurgeMessages::class,
            PurgeThreads::class,
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/messenger.php' => config_path('messenger.php'),
        ], 'messenger.config');

        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/messenger'),
        ], 'messenger.views');

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/messenger'),
        ], 'messenger.assets');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'messenger.migrations');

        if ($this->app['config']->get('messenger.calling.driver') === 'janus') {
            $this->publishes([
                __DIR__.'/../config/janus.php' => config_path('janus.php'),
            ], 'messenger.janus.config');
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
            VideoDriver::class,
            $this->getVideoImplementation()
        );
    }

    /**
     * Register our middleware.
     *
     * @throws BindingResolutionException
     */
    protected function registerMiddleware(): void
    {
        $this->app->make(Kernel::class)
            ->prependToMiddlewarePriority(MessengerApi::class);

        $router = $this->app->make(Router::class);

        $router->aliasMiddleware(
            'messenger.api',
            MessengerApi::class
        );
        $router->aliasMiddleware(
            'messenger.provider',
            SetMessengerProvider::class
        );
        $router->aliasMiddleware(
            'auth.optional',
            AuthenticateOptional::class
        );
    }

    /**
     * Configure the rate limiters for Messenger.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('messenger-api', function (Request $request) {
            return Limit::perMinute(120)->by(optional($request->user())->getKey() ?: $request->ip());
        });

        RateLimiter::for('messenger-message', function (Request $request) {
            $thread = $request->route()->originalParameter('thread');
            $user = optional($request->user())->getKey() ?: $request->ip();

            return Limit::perMinute(60)->by($thread.'.'.$user);
        });

        RateLimiter::for('messenger-search', function (Request $request) {
            return Limit::perMinute(45)->by(optional($request->user())->getKey() ?: $request->ip());
        });
    }

    /**
     * Get the driver set in config for our services broadcasting feature.
     *
     * @return string
     */
    protected function getBroadcastImplementation(): string
    {
        $alias = $this->app['config']->get('messenger.broadcasting.driver');

        return $this->app['config']->get('messenger.drivers.broadcasting')[$alias ?? 'null'];
    }

    /**
     * Get the driver set in config for our services video feature.
     *
     * @return string
     */
    protected function getVideoImplementation(): string
    {
        $alias = $this->app['config']->get('messenger.calling.driver');

        return $this->app['config']->get('messenger.drivers.calling')[$alias ?? 'null'];
    }

    /**
     * Sanitize user defined middleware in case not array.
     *
     * @param $middleware
     * @return array
     */
    protected function mergeApiMiddleware($middleware): array
    {
        $merged = array_merge(['messenger.api'], is_array($middleware) ? $middleware : [$middleware]);

        array_push($merged, 'throttle:messenger-api');

        return $merged;
    }
}
