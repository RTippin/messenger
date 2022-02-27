<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PushNotificationEvent
{
    use SerializesModels;

    /**
     * @param  string  $broadcastAs
     * @param  array  $data
     * @param  Collection  $recipients
     */
    public function __construct(
        public string $broadcastAs,
        public array $data,
        public Collection $recipients
    ){}
}
