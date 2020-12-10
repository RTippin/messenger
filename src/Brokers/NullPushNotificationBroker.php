<?php

namespace RTippin\Messenger\Brokers;

use Illuminate\Support\Collection;
use RTippin\Messenger\Contracts\PushNotificationDriver;

class NullPushNotificationBroker implements PushNotificationDriver
{
    public function to(Collection $recipients): self
    {
        return $this;
    }

    public function with(array $resource): self
    {
        return $this;
    }

    public function notify(string $abstract): void
    {
        //
    }
}
