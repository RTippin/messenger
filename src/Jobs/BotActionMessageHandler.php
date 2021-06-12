<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Services\BotService;

class BotActionMessageHandler implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var Message
     */
    private Message $message;

    /**
     * Create a new job instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @param BotService $service
     * @return void
     * @throws BotException
     */
    public function handle(BotService $service): void
    {
        $service->handleMessage($this->message);
    }
}
