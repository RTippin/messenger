<?php

namespace RTippin\Messenger\Contracts;

use Illuminate\Support\Collection;
use RTippin\Messenger\Models\Thread;

/**
 * Select a single TO method, set the resource to broadcast, then trigger
 * broadcast given the abstract string of the BroadcastEvent.
 */
interface BroadcastDriver
{
    /**
     * Generate all available participants for the current
     * thread to broadcast to.
     *
     * @param  Thread  $thread
     * @return $this
     */
    public function toAllInThread(Thread $thread): self;

    /**
     * Generate all available participants for the current thread,
     * excluding current provider.
     *
     * @param  Thread  $thread
     * @return $this
     */
    public function toOthersInThread(Thread $thread): self;

    /**
     * Set recipients to the provided collection. Collection may
     * contain a mix of messenger providers or any of our
     * internal models that implement Ownerable.
     *
     * @param  Collection  $recipients
     * @return $this
     */
    public function toSelected(Collection $recipients): self;

    /**
     * Set recipient to a single instance of the types listed below.
     *
     * @param  MessengerProvider|Ownerable|mixed  $recipient
     * @return $this
     */
    public function to($recipient): self;

    /**
     * Set single presence channel to broadcast on. Accepts Call or Thread.
     *
     * @param  HasPresenceChannel|mixed  $entity
     * @return $this
     */
    public function toPresence($entity): self;

    /**
     * Set many presence channels to broadcast on. Collection may
     * contain a mix of Call and Thread models.
     *
     * @param  Collection  $presence
     * @return $this
     */
    public function toManyPresence(Collection $presence): self;

    /**
     * Set the resource we will use to broadcast out.
     *
     * @param  array  $with
     * @return $this
     */
    public function with(array $with): self;

    /**
     * Check the abstract event class implements our interface so that we may
     * inject the channels and resource, then broadcast the resource!
     *
     * @param  string|BroadcastEvent  $abstract
     */
    public function broadcast(string $abstract): void;
}
