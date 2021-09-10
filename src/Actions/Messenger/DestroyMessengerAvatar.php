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
     * @param  Messenger  $messenger
     * @param  FileService  $fileService
     */
    public function __construct(Messenger $messenger, FileService $fileService)
    {
        parent::__construct($messenger, $fileService);
    }

    /**
     * @return $this
     *
     * @throws FeatureDisabledException
     */
    public function execute(): self
    {
        $this->bailWhenFeatureDisabled();

        $this->removeOldIfExist()->updateProviderAvatar(null);

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function bailWhenFeatureDisabled(): void
    {
        if (! $this->messenger->isProviderAvatarEnabled()) {
            throw new FeatureDisabledException('Avatar removal is currently disabled.');
        }
    }
}
