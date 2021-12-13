<?php

namespace RTippin\Messenger\Actions\Messenger;

use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Services\FileService;

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
     * @param  Messenger  $messenger
     * @param  FileService  $fileService
     */
    public function __construct(Messenger $messenger, FileService $fileService)
    {
        $this->fileService = $fileService;
        $this->messenger = $messenger;
    }

    /**
     * @return $this
     */
    protected function removeOldIfExist(): self
    {
        if (! is_null($this->messenger->getProvider()->{$this->messenger->getProvider()->getProviderAvatarColumn()})) {
            $this->fileService
                ->setDisk($this->messenger->getAvatarStorage('disk'))
                ->destroy("{$this->getDirectory()}/{$this->messenger->getProvider()->{$this->messenger->getProvider()->getProviderAvatarColumn()}}");
        }

        return $this;
    }

    /**
     * @return string
     */
    protected function getDirectory(): string
    {
        return "{$this->messenger->getAvatarStorage('directory')}/{$this->messenger->getProviderAlias()}/{$this->messenger->getProvider()->getKey()}";
    }

    /**
     * @param  string|null  $file
     * @return void
     */
    protected function updateProviderAvatar(?string $file): void
    {
        $this->messenger->getProvider()->forceFill([
            $this->messenger->getProvider()->getProviderAvatarColumn() => $file,
        ])->save();
    }
}
