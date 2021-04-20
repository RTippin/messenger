<?php

namespace RTippin\Messenger;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use RTippin\Messenger\Broadcasting\CallChannel;
use RTippin\Messenger\Broadcasting\ProviderChannel;
use RTippin\Messenger\Broadcasting\ThreadChannel;

/**
 * @property-read Application $app
 */
trait ChannelMap
{
    /**
     * Register all broadcast channels used by messenger.
     *
     * @throws BindingResolutionException
     */
    private function registerChannels()
    {
        if ($this->app['config']->get('messenger.routing.channels.enabled')) {
            $this->app->make(BroadcastManager::class)->routes($this->channelRouteConfiguration());

            $broadcaster = $this->app->make(Broadcaster::class);

            $broadcaster->channel('messenger.thread.{thread}', ThreadChannel::class);
            $broadcaster->channel('messenger.call.{call}.thread.{thread}', CallChannel::class);
            $broadcaster->channel('messenger.{alias}.{id}', ProviderChannel::class);
        }
    }

    /**
     * Get the Broadcasting channel route group configuration array.
     *
     * @return array
     */
    private function channelRouteConfiguration(): array
    {
        return [
            'domain' => $this->app['config']->get('messenger.routing.channels.domain'),
            'prefix' => $this->app['config']->get('messenger.routing.channels.prefix'),
            'middleware' => $this->mergeApiMiddleware($this->app['config']->get('messenger.routing.channels.middleware')),
        ];
    }
}
