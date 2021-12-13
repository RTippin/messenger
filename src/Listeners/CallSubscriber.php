<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Events\Dispatcher;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Events\CallLeftEvent;
use RTippin\Messenger\Events\CallStartedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\EndCallIfEmpty;
use RTippin\Messenger\Jobs\SetupCall;
use RTippin\Messenger\Jobs\TeardownCall;

class CallSubscriber
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param  Dispatcher  $events
     * @return void
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(CallEndedEvent::class, [CallSubscriber::class, 'teardownCall']);
        $events->listen(CallLeftEvent::class, [CallSubscriber::class, 'endCallIfEmpty']);
        $events->listen(CallStartedEvent::class, [CallSubscriber::class, 'setupCall']);
    }

    /**
     * @param  CallEndedEvent  $event
     * @return void
     */
    public function teardownCall(CallEndedEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? TeardownCall::dispatch($event)->onQueue(Messenger::getCallSubscriber('channel'))
                : TeardownCall::dispatchSync($event);
        }
    }

    /**
     * @param  CallLeftEvent  $event
     * @return void
     */
    public function endCallIfEmpty(CallLeftEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? EndCallIfEmpty::dispatch($event)->onQueue(Messenger::getCallSubscriber('channel'))
                : EndCallIfEmpty::dispatchSync($event);
        }
    }

    /**
     * @param  CallStartedEvent  $event
     * @return void
     */
    public function setupCall(CallStartedEvent $event): void
    {
        if ($this->isEnabled() && ! $event->call->setup_complete) {
            $this->shouldQueue()
                ? SetupCall::dispatch($event)->onQueue(Messenger::getCallSubscriber('channel'))
                : SetupCall::dispatchSync($event);
        }
    }

    /**
     * @return bool
     */
    private function isEnabled(): bool
    {
        return Messenger::getCallSubscriber('enabled');
    }

    /**
     * @return bool
     */
    private function shouldQueue(): bool
    {
        return Messenger::getCallSubscriber('queued');
    }
}
