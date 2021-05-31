<?php

namespace RTippin\Messenger;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use RTippin\Messenger\Listeners\CallSubscriber;
use RTippin\Messenger\Listeners\SystemMessageSubscriber;

/**
 * @property-read Application $app
 */
trait EventMap
{
    /**
     * Register the Event Subscribers.
     *
     * @return void
     * @throws BindingResolutionException
     */
    private function registerListeners()
    {
        $events = $this->app->make(Dispatcher::class);

        $events->subscribe(CallSubscriber::class);
        $events->subscribe(SystemMessageSubscriber::class);
    }
}
