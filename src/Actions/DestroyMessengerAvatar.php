<?php

namespace RTippin\Messenger\Actions;

use RTippin\Messenger\Actions\Base\BaseMessengerAction;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Services\Messenger\FileService;

class DestroyMessengerAvatar extends BaseMessengerAction
{
    /**
     * @var FileService
     */
    private FileService $fileService;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * StoreMessengerAvatar constructor.
     *
     * @param Messenger $messenger
     * @param FileService $fileService
     */
    public function __construct(Messenger $messenger,
                                FileService $fileService)
    {
        $this->fileService = $fileService;
        $this->messenger = $messenger;
    }

    /**
     * @param mixed ...$parameters
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->removeOldIfExist();

        return $this;
    }

    /**
     * @return $this
     */
    private function removeOldIfExist(): self
    {
        if( ! is_null($this->messenger->getProvider()->picture))
        {
            $this->fileService
                ->setDisk($this->messenger->getAvatarStorage('disk'))
                ->destroy(
                    "{$this->getDirectory()}/{$this->messenger->getProvider()->picture}"
                );

            $this->messenger->getProvider()->update([
                'picture' => null
            ]);
        }

        return $this;
    }

    /**
     * @return string
     */
    private function getDirectory(): string
    {
        return "{$this->messenger->getAvatarStorage('directory')}/{$this->messenger->getProviderAlias()}/{$this->messenger->getProviderId()}";
    }
}