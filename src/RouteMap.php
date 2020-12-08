<?php

namespace RTippin\Messenger;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;

/**
 * @property-read Application $app
 */
trait RouteMap
{
    /**
     * Register all routes used by messenger.
     * @throws BindingResolutionException
     */
    protected function registerRoutes(): void
    {
        $router = $this->app->make(Router::class);

        if ($this->app['config']->get('messenger.routing.api.enabled')) {
            $router->group($this->apiRouteConfiguration(), function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
            });
            $router->group($this->apiRouteConfiguration(true), function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/invite_api.php');
            });
        }

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
