<?php

namespace RTippin\Messenger\Events;

use Throwable;

class BroadcastFailedEvent
{
    /**
     * @param  string  $abstractBroadcast
     * @param  array  $channels
     * @param  array  $with
     * @param  Throwable  $exception
     */
    public function __construct(
        public string $abstractBroadcast,
        public array $channels,
        public array $with,
        public Throwable $exception
    ){}
}
