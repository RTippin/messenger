<?php

namespace RTippin\Messenger\Actions\Threads;

use Exception;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\FileServiceException;
use RTippin\Messenger\Models\Thread;

class DestroyGroupAvatar extends GroupAvatarAction
{
    /**
     * @param  Thread  $thread
     * @return $this
     * @var Thread[0]
     * @throws FeatureDisabledException|FileServiceException|Exception
     */
    public function execute(Thread $thread): self
    {
        $this->bailWhenFeatureDisabled();

        $this->setThread($thread)
            ->removeOldIfExist()
            ->updateGroupAvatar(null)
            ->generateResource();

        if ($this->getThread()->wasChanged()) {
            $this->fireBroadcast()->fireEvents();
        }

        return $this;
    }
}
