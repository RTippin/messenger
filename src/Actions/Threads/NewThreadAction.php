<?php

namespace RTippin\Messenger\Actions\Threads;

use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Http\Resources\Broadcast\NewThreadBroadcastResource;
use RTippin\Messenger\Http\Resources\ThreadResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;

abstract class NewThreadAction extends BaseMessengerAction
{
    /**
     * @var bool
     */
    protected bool $pending = false;

    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * NewThreadAction constructor.
     *
     * @param  Messenger  $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * Store a fresh new thread.
     *
     * @param  array  $attributes
     * @return $this
     */
    protected function storeThread(array $attributes = []): self
    {
        $this->setThread(Thread::create(array_merge(Thread::DefaultSettings, $attributes)));

        return $this;
    }

    /**
     * Generate the thread resource.
     *
     * @return $this
     */
    protected function generateResource(): self
    {
        $this->setJsonResource(new ThreadResource(
            $this->getThread()->fresh(),
            true
        ));

        return $this;
    }

    /**
     * @return array
     */
    protected function generateBroadcastResource(): array
    {
        return (new NewThreadBroadcastResource(
            $this->messenger->getProvider(),
            $this->getThread(),
            $this->pending
        ))->resolve();
    }
}
