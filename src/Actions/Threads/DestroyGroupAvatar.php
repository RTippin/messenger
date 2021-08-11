<?php

namespace RTippin\Messenger\Actions\Threads;

use Exception;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\FileServiceException;
use RTippin\Messenger\Models\Thread;

class DestroyGroupAvatar extends GroupAvatarAction
{
    /**
     * @param mixed ...$parameters
     * @return $this
     * @var Thread[0]
     * @throws FeatureDisabledException|FileServiceException|Exception
     */
    public function execute(...$parameters): self
    {
        $this->bailWhenFeatureDisabled();

        $this->setThread($parameters[0])
            ->removeOldIfExist()
            ->updateGroupAvatar(null)
            ->generateResource();

        if ($this->getThread()->wasChanged()) {
            $this->fireBroadcast()->fireEvents();
        }

        return $this;
    }
}
