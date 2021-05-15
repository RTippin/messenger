<?php

namespace RTippin\Messenger\Actions\Messenger;

use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Services\FileService;

class DestroyMessengerAvatar extends MessengerAvatarAction
{
    /**
     * DestroyMessengerAvatar constructor.
     *
     * @param Messenger $messenger
     * @param FileService $fileService
     */
    public function __construct(Messenger $messenger, FileService $fileService)
    {
        parent::__construct($messenger, $fileService);
    }

    /**
     * @param mixed ...$parameters
     * @return $this
     * @throws FeatureDisabledException
     */
    public function execute(...$parameters): self
    {
        $this->isAvatarRemovalEnabled();

        $this->removeOldIfExist()->updateProviderAvatar(null);

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function isAvatarRemovalEnabled(): void
    {
        if (! $this->messenger->isProviderAvatarRemovalEnabled()) {
            throw new FeatureDisabledException('Avatar removal is currently disabled.');
        }
    }
}
