<?php

namespace RTippin\Messenger\Actions;

use RTippin\Messenger\Actions\Base\BaseMessengerAction;
use RTippin\Messenger\FileService;
use RTippin\Messenger\Messenger;

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
     * DestroyMessengerAvatar constructor.
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
        if (! is_null($this->messenger->getProvider()->{$this->messenger->getProvider()->getAvatarColumn()})) {
            $this->fileService
                ->setDisk($this->messenger->getAvatarStorage('disk'))
                ->destroy(
                    "{$this->getDirectory()}/{$this->messenger->getProvider()->{$this->messenger->getProvider()->getAvatarColumn()}}"
                );

            $this->messenger->getProvider()->update([
                $this->messenger->getProvider()->getAvatarColumn() => null,
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
