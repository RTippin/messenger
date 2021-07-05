<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Bots\ProcessMessageTriggers;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;

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
     * @param ProcessMessageTriggers $process
     * @return void
     * @throws FeatureDisabledException
     */
    public function handle(ProcessMessageTriggers $process): void
    {
        $process->execute(
            $this->event->thread,
            $this->event->message,
            $this->event->isGroupAdmin,
            $this->event->senderIp
        );
    }
}
