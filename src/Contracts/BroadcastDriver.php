<?php

namespace RTippin\Messenger\Contracts;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use Illuminate\Support\Collection;

/**
 * Select a single TO method, set the resource to broadcast, then trigger
 * broadcast given the abstract string of the BroadcastEvent
 */
interface BroadcastDriver
{
    /**
     * Generate all available participants for the current
     * thread to broadcast to
     *
     * @param Thread $thread
     * @return $this
     */
    public function toAllInThread(Thread $thread): self;

    /**
     * Generate all available participants for the current thread,
     * excluding current provider
     *
     * @param Thread $thread
     * @return $this
     */
    public function toOthersInThread(Thread $thread): self;

    /**
     * Set recipients to the provided collection. Collection may
     * contain a mix of messenger providers, thread participants,
     * or call participants
     *
     * @param Collection $recipients
     * @return $this
     */
    public function toSelected(Collection $recipients): self;

    /**
     * Set recipient to a single instance of the types listed below
     *
     * @param MessengerProvider|Participant|CallParticipant $recipient
     * @return $this
     */
    public function to($recipient): self;

    /**
     * Set single presence channel to broadcast on. Accepts Call or Thread
     *
     * @param Call|Thread $entity
     * @return $this
     */
    public function toPresence($entity): self;

    /**
     * Set many presence channels to broadcast on. Collection may
     * contain a mix of Call and Thread models
     *
     * @param Collection $presence
     * @return $this
     */
    public function toManyPresence(Collection $presence): self;

    /**
     * Set the resource we will use to broadcast out
     *
     * @param array $with
     * @return $this
     */
    public function with(array $with): self;

    /**
     * Check the abstract event class implements our contract so that we may
     * inject the channels and resource, then broadcast the resource!
     *
     * @param string|BroadcastEvent $abstract
     */
    public function broadcast(string $abstract): void;
}