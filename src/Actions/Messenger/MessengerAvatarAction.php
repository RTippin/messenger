<?php

namespace RTippin\Messenger\Actions\Messenger;

use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\FileService;
use RTippin\Messenger\Messenger;

abstract class MessengerAvatarAction extends BaseMessengerAction
{
    /**
     * @var FileService
     */
    protected FileService $fileService;

    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * MessengerAvatarAction constructor.
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
     * @return $this
     */
    protected function removeOldIfExist(): self
    {
        if (! is_null($this->messenger->getProvider()->{$this->messenger->getProvider()->getAvatarColumn()})) {
            $this->fileService
                ->setDisk($this->messenger->getAvatarStorage('disk'))
                ->destroy(
                    "{$this->getDirectory()}/{$this->messenger->getProvider()->{$this->messenger->getProvider()->getAvatarColumn()}}"
                );
        }

        return $this;
    }

    /**
     * @return string
     */
    protected function getDirectory(): string
    {
        return "{$this->messenger->getAvatarStorage('directory')}/{$this->messenger->getProviderAlias()}/{$this->messenger->getProviderId()}";
    }

    /**
     * @param string|null $file
     */
    protected function updateProviderAvatar(?string $file): void
    {
        $this->messenger->getProvider()->update([
            $this->messenger->getProvider()->getAvatarColumn() => $file,
        ]);
    }
}
