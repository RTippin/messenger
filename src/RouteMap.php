<?php

namespace RTippin\Messenger;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Middleware\AuthenticateOptional;
use RTippin\Messenger\Http\Middleware\MessengerApi;
use RTippin\Messenger\Http\Middleware\SetMessengerProvider;

/**
 * @property-read Application $app
 */
trait RouteMap
{
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
            'messenger.provider',
            SetMessengerProvider::class
        );
        $router->aliasMiddleware(
            'auth.optional',
            AuthenticateOptional::class
        );
    }

    /**
     * Register all routes used by messenger.
     *
     * @throws BindingResolutionException
     */
    protected function registerRoutes(): void
    {
        $router = $this->app->make(Router::class);

        $router->group($this->apiRouteConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
        $router->group($this->apiRouteConfiguration(true), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/invite_api.php');
        });

        if ($this->app['config']->get('messenger.routing.web.enabled')) {
            $router->group($this->webRouteConfiguration(), function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
            $router->group($this->webRouteConfiguration(true), function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/invite_web.php');
            });
        }

        if ($this->app['config']->get('messenger.routing.provider_avatar.enabled')) {
            $router->group($this->providerAvatarRouteConfiguration(), function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/avatar.php');
            });
        }
    }

    /**
     * Configure the rate limiters for Messenger.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('messenger-api', function (Request $request) {
            $limit = Messenger::getApiRateLimit();

            return $limit > 0
                ? Limit::perMinute($limit)->by(optional($request->user())->getKey() ?: $request->ip())
                : Limit::none();
        });

        RateLimiter::for('messenger-message', function (Request $request) {
            $thread = $request->route()->originalParameter('thread');
            $user = optional($request->user())->getKey() ?: $request->ip();
            $limit = Messenger::getMessageRateLimit();

            return $limit > 0
                ? Limit::perMinute($limit)->by($thread.'.'.$user)
                : Limit::none();
        });

        RateLimiter::for('messenger-attachment', function (Request $request) {
            $thread = $request->route()->originalParameter('thread');
            $user = optional($request->user())->getKey() ?: $request->ip();
            $limit = Messenger::getAttachmentRateLimit();

            return $limit > 0
                ? Limit::perMinute($limit)->by($thread.'.'.$user)
                : Limit::none();
        });

        RateLimiter::for('messenger-search', function (Request $request) {
            $limit = Messenger::getSearchRateLimit();

            return $limit > 0
                ? Limit::perMinute($limit)->by(optional($request->user())->getKey() ?: $request->ip())
                : Limit::none();
        });
    }

    /**
     * Get the Messenger API route group configuration array.
     *
     * @param bool $invite
     * @return array
     */
    protected function apiRouteConfiguration(bool $invite = false): array
    {
        return [
            'domain' => $this->app['config']->get('messenger.routing.api.domain'),
            'prefix' => trim($this->app['config']->get('messenger.routing.api.prefix'), '/'),
            'middleware' => $invite
                ? $this->mergeApiMiddleware($this->app['config']->get('messenger.routing.api.invite_api_middleware'))
                : $this->mergeApiMiddleware($this->app['config']->get('messenger.routing.api.middleware')),
        ];
    }

    /**
     * Get the Messenger API route group configuration array.
     *
     * @param bool $invite
     * @return array
     */
    protected function webRouteConfiguration(bool $invite = false): array
    {
        return [
            'domain' => $this->app['config']->get('messenger.routing.web.domain'),
            'prefix' => trim($this->app['config']->get('messenger.routing.web.prefix'), '/'),
            'middleware' => $invite
                ? $this->app['config']->get('messenger.routing.web.invite_web_middleware')
                : $this->app['config']->get('messenger.routing.web.middleware'),
        ];
    }

    /**
     * Get the Messenger API route group configuration array.
     *
     * @return array
     */
    protected function providerAvatarRouteConfiguration(): array
    {
        return [
            'domain' => $this->app['config']->get('messenger.routing.provider_avatar.domain'),
            'prefix' => trim($this->app['config']->get('messenger.routing.provider_avatar.prefix'), '/'),
            'middleware' => $this->app['config']->get('messenger.routing.provider_avatar.middleware'),
        ];
    }
}
