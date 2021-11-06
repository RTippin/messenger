<?php

namespace RTippin\Messenger\Events;

use Throwable;

class BroadcastFailedEvent
{
    /**
     * @var string
     */
    public string $abstractBroadcast;

    /**
     * @var array
     */
    public array $channels;

    /**
     * @var Throwable
     */
    public Throwable $exception;

    /**
     * @var array
     */
    public array $with;

    /**
     * Create a new event instance.
     *
     * @param  string  $abstractBroadcast
     * @param  array  $channels
     * @param  array  $with
     * @param  Throwable  $exception
     */
    public function __construct(string $abstractBroadcast,
                                array $channels,
                                array $with,
                                Throwable $exception)
    {
        $this->abstractBroadcast = $abstractBroadcast;
        $this->channels = $channels;
        $this->with = $with;
        $this->exception = $exception;
    }
}
