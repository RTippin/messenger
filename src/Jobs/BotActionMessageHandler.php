<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Services\BotService;

class BotActionMessageHandler implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var NewMessageEvent
     */
    private NewMessageEvent $event;

    /**
     * Create a new job instance.
     */
    public function __construct(NewMessageEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @param BotService $service
     * @return void
     */
    public function handle(BotService $service): void
    {
        $service->handleMessage(
            $this->event->message,
            $this->event->thread,
            $this->event->isGroupAdmin,
            $this->event->senderIp
        );
    }
}
