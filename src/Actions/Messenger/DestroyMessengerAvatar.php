<?php

namespace RTippin\Messenger\Actions\Messenger;

use RTippin\Messenger\FileService;
use RTippin\Messenger\Messenger;

class DestroyMessengerAvatar extends MessengerAvatarAction
{
    /**
     * DestroyMessengerAvatar constructor.
     *
     * @param Messenger $messenger
     * @param FileService $fileService
     */
    public function __construct(Messenger $messenger,
                                FileService $fileService)
    {
        parent::__construct($messenger, $fileService);
    }

    /**
     * @param mixed ...$parameters
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->removeOldIfExist()
            ->updateProviderAvatar(null);

        return $this;
    }
}
