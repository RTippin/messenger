<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\Base\BaseMessengerAction;
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
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
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
     * @param mixed ...$parameters
     * @var Thread[0]
     * @var ThreadSettingsRequest[1]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->checkIfGroupNameChanged($parameters[1]['subject'])
            ->updateThread($parameters[1])
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @param array $attributes
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
     * @param string $name
     * @return $this
     */
    private function checkIfGroupNameChanged(string $name): self
    {
        $this->nameChanged = $this->getThread()->subject !== $name;

        return $this;
    }

    /**
     * Generate the thread settings resource.
     *
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
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ThreadSettingsEvent(
                $this->messenger->getProvider()->withoutRelations(),
                $this->getThread(true),
                $this->nameChanged
            ));
        }

        return $this;
    }
}
