<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PushNotificationEvent
{
    use SerializesModels;

    /**
     * @var Collection
     */
    public Collection $recipients;

    /**
     * @var string
     */
    public string $broadcastAs;

    /**
     * @var array
     */
    public array $data;

    /**
     * Create a new event instance.
     *
     * @param  string  $broadcastAs
     * @param  array  $data
     * @param  Collection  $recipients
     */
    public function __construct(string $broadcastAs,
                                array $data,
                                Collection $recipients)
    {
        $this->broadcastAs = $broadcastAs;
        $this->recipients = $recipients;
        $this->data = $data;
    }
}
