<?php

namespace RTippin\Messenger\Actions\Invites;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Events\InviteArchivedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Invite;

class ArchiveInvite extends InviteAction
{
    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var Invite
     */
    private Invite $invite;

    /**
     * ArchiveInvite constructor.
     *
     * @param  Messenger  $messenger
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(Messenger $messenger, Dispatcher $dispatcher)
    {
        parent::__construct($messenger);

        $this->dispatcher = $dispatcher;
    }

    /**
     * @param  Invite  $invite
     * @return $this
     *
     * @throws Exception|FeatureDisabledException
     */
    public function execute(Invite $invite): self
    {
        $this->bailIfDisabled();

        $this->invite = $invite;

        $this->archiveInvite()->fireEvents();

        return $this;
    }

    /**
     * @return $this
     *
     * @throws Exception
     */
    private function archiveInvite(): self
    {
        $this->invite->delete();

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new InviteArchivedEvent(
                $this->messenger->getProvider(true),
                $this->invite->withoutRelations()
            ));
        }
    }
}
