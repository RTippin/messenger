<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ThreadSettingsBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\ThreadSettingsEvent;
use RTippin\Messenger\Http\Request\ThreadSettingsRequest;
use RTippin\Messenger\Http\Resources\Broadcast\ThreadSettingsBroadcastResource;
use RTippin\Messenger\Http\Resources\ThreadSettingsResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;

class UpdateGroupSettings extends BaseMessengerAction
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var bool
     */
    private bool $nameChanged;

    /**
     * UpdateGroupSettings constructor.
     *
     * @param  Messenger  $messenger
     * @param  BroadcastDriver  $broadcaster
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher)
    {
        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
    }

    /**
     * Update the group settings if anything changed. We push the
     * changes over the presence channel and an event for when
     * the group name changes.
     *
     * @param  Thread  $thread
     * @param  array  $params
     * @return $this
     *
     * @see ThreadSettingsRequest
     */
    public function execute(Thread $thread, array $params): self
    {
        $this->setThread($thread)
            ->determineIfGroupNameChanged($params['subject'])
            ->updateThread($params)
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @param  array  $attributes
     * @return $this
     */
    private function updateThread(array $attributes): self
    {
        $this->getThread()->timestamps = false;

        $this->getThread()->update($attributes);

        if (! $this->getThread()->wasChanged()) {
            $this->withoutDispatches();
        }

        return $this;
    }

    /**
     * @param  string  $name
     * @return $this
     */
    private function determineIfGroupNameChanged(string $name): self
    {
        $this->nameChanged = $this->getThread()->subject !== $name;

        return $this;
    }

    /**
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new ThreadSettingsResource(
            $this->getThread()
        ));

        return $this;
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return (new ThreadSettingsBroadcastResource(
            $this->messenger->getProvider(),
            $this->getThread()
        ))->resolve();
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->toPresence($this->getThread())
                ->with($this->generateBroadcastResource())
                ->broadcast(ThreadSettingsBroadcast::class);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ThreadSettingsEvent(
                $this->messenger->getProvider(true),
                $this->getThread(true),
                $this->nameChanged
            ));
        }
    }
}
