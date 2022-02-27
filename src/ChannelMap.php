<?php

namespace RTippin\Messenger;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use RTippin\Messenger\Broadcasting\Channels\CallChannel;
use RTippin\Messenger\Broadcasting\Channels\ProviderChannel;
use RTippin\Messenger\Broadcasting\Channels\ThreadChannel;

/**
 * @property-read Application $app
 */
trait ChannelMap
{
    /**
     * Register all broadcast channels used by messenger.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    private function registerChannels(): void
    {
        if (config('messenger.routing.channels.enabled')) {
            $this->app->make(BroadcastManager::class)->routes($this->channelRouteConfiguration());

            $broadcaster = $this->app->make(Broadcaster::class);

            $broadcaster->channel(
                channel: 'messenger.thread.{thread}',
                callback: ThreadChannel::class
            );

            $broadcaster->channel(
                channel: 'messenger.call.{call}.thread.{thread}',
                callback: CallChannel::class
            );

            $broadcaster->channel(
                channel: 'messenger.{alias}.{id}',
                callback: ProviderChannel::class
            );
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
            'domain' => config('messenger.routing.channels.domain'),
            'prefix' => config('messenger.routing.channels.prefix'),
            'middleware' => $this->mergeApiMiddleware(config('messenger.routing.channels.middleware')),
        ];
    }
}
