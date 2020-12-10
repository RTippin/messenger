<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use RTippin\Messenger\Events\StatusHeartbeatEvent;
use RTippin\Messenger\Messenger;

class StoreMessengerIp implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public ?string $queue = 'messenger';

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * Create the event listener.
     *
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * Handle the event.
     *
     * @param StatusHeartbeatEvent $event
     * @return void
     */
    public function handle(StatusHeartbeatEvent $event): void
    {
        $this->messenger
            ->getProviderMessenger($event->provider)
            ->update([
                'ip' => $event->IP,
            ]);
    }

    /**
     * Determine whether the listener should be queued.
     *
     * @param StatusHeartbeatEvent $event
     * @return bool
     */
    public function shouldQueue(StatusHeartbeatEvent $event): bool
    {
        return $this->messenger->getProviderMessenger($event->provider)->ip !== $event->IP;
    }
}
