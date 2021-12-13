<?php

namespace RTippin\Messenger\Actions\Invites;

use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Messenger;

abstract class InviteAction extends BaseMessengerAction
{
    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * InviteAction constructor.
     *
     * @param  Messenger  $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @throws FeatureDisabledException
     */
    protected function bailIfDisabled(): void
    {
        if (! $this->messenger->isThreadInvitesEnabled()) {
            throw new FeatureDisabledException('Group invites are currently disabled.');
        }
    }
}
