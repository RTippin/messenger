<?php

namespace RTippin\Messenger;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Broadcast;
use RTippin\Messenger\Broadcasting\CallChannel;
use RTippin\Messenger\Broadcasting\ProviderChannel;
use RTippin\Messenger\Broadcasting\ThreadChannel;

/**
 * @property-read Application $app
 */
trait ChannelMap
{
    /**
     * Register all broadcast channels used by messenger
     */
    protected function registerChannels()
    {
        if($this->app['config']->get('messenger.routing.channels.enabled'))
        {
            Broadcast::routes($this->channelRouteConfiguration());

            Broadcast::channel('{alias}.{id}', ProviderChannel::class);

            Broadcast::channel('call.{call}.thread.{thread}', CallChannel::class);

            Broadcast::channel('thread.{thread}', ThreadChannel::class);
        }
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
}