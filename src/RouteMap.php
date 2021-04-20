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
    private function registerMiddleware(): void
    {
        $this->app->make(Kernel::class)->prependToMiddlewarePriority(MessengerApi::class);

        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('messenger.provider', SetMessengerProvider::class);

        $router->aliasMiddleware('auth.optional', AuthenticateOptional::class);
    }

    /**
     * Register all routes used by messenger.
     *
     * @throws BindingResolutionException
     */
    private function registerRoutes(): void
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
    private function configureRateLimiting(): void
    {
        RateLimiter::for('messenger-api', function (Request $request) {
            return Messenger::getApiRateLimit() > 0
                ? Limit::perMinute(Messenger::getApiRateLimit())->by(optional($request->user())->getKey() ?: $request->ip())
                : Limit::none();
        });

        RateLimiter::for('messenger-message', function (Request $request) {
            return Messenger::getMessageRateLimit() > 0
                ? Limit::perMinute(Messenger::getMessageRateLimit())->by(
                    $request->route()->originalParameter('thread').'.'.optional($request->user())->getKey() ?: $request->ip()
                )
                : Limit::none();
        });

        RateLimiter::for('messenger-attachment', function (Request $request) {
            return Messenger::getAttachmentRateLimit() > 0
                ? Limit::perMinute(Messenger::getAttachmentRateLimit())->by(
                    $request->route()->originalParameter('thread').'.'.optional($request->user())->getKey() ?: $request->ip()
                )
                : Limit::none();
        });

        RateLimiter::for('messenger-search', function (Request $request) {
            return Messenger::getSearchRateLimit() > 0
                ? Limit::perMinute(Messenger::getSearchRateLimit())->by(optional($request->user())->getKey() ?: $request->ip())
                : Limit::none();
        });
    }

    /**
     * Get the Messenger API route group configuration array.
     *
     * @param bool $invite
     * @return array
     */
    private function apiRouteConfiguration(bool $invite = false): array
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
    private function webRouteConfiguration(bool $invite = false): array
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
    private function providerAvatarRouteConfiguration(): array
    {
        return [
            'domain' => $this->app['config']->get('messenger.routing.provider_avatar.domain'),
            'prefix' => trim($this->app['config']->get('messenger.routing.provider_avatar.prefix'), '/'),
            'middleware' => $this->app['config']->get('messenger.routing.provider_avatar.middleware'),
        ];
    }
}
