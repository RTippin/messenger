<?php

namespace RTippin\Messenger\Actions\Invites;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Events\InviteArchivedEvent;
use RTippin\Messenger\Models\Invite;

class ArchiveInvite extends BaseMessengerAction
{
    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * ArchiveInvite constructor.
     *
     * @param Dispatcher $dispatcher
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param mixed ...$parameters
     * @var Invite[0]
     * @return $this
     * @throws Exception
     */
    public function execute(...$parameters): self
    {
        $this->setData($parameters[0])
            ->archiveInvite()
            ->fireEvents();

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    private function archiveInvite(): self
    {
        $this->getData()->delete();

        return $this;
    }

    /**
     * Broadcast / fire events.
     *
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new InviteArchivedEvent(
                $this->getData(true)
            ));
        }

        return $this;
    }
}
