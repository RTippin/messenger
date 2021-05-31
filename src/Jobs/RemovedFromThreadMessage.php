<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Events\RemovedFromThreadEvent;
use RTippin\Messenger\Support\MessageTransformer;
use Throwable;

class RemovedFromThreadMessage implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var RemovedFromThreadEvent
     */
    private RemovedFromThreadEvent $event;

    /**
     * Create a new job instance.
     *
     * @param RemovedFromThreadEvent $event
     */
    public function __construct(RemovedFromThreadEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @param StoreSystemMessage $message
     * @return void
     * @throws Throwable
     */
    public function handle(StoreSystemMessage $message): void
    {
        $message->execute(...$this->systemMessage());
    }

    /**
     * @return array
     */
    private function systemMessage(): array
    {
        return MessageTransformer::makeRemovedFromGroup(
            $this->event->thread,
            $this->event->provider,
            $this->event->participant
        );
    }
}
