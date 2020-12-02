<?php

namespace RTippin\Messenger\Contracts;

use Illuminate\Support\Collection;

interface PushNotificationDriver
{
    /**
     * Set recipients to the provided collection. Collection may
     * contain a mix of messenger providers, thread participants,
     * or call participants
     *
     * @param Collection $recipients
     * @return $this
     */
    public function to(Collection $recipients): self;

    /**
     * Set the resource we will use to broadcast out
     *
     * @param array $resource
     * @return $this
     */
    public function with(array $resource): self;

    /**
     * We will use the abstract broadcast event to get the name of the notification,
     * then extract all providers from the given resource collection, and remove any
     * that do not have devices enabled, then fire the event with the formatted data
     * for our listener to handle on the queue.
     *
     * @param string|BroadcastEvent $abstract
     */
    public function notify(string $abstract): void;
}