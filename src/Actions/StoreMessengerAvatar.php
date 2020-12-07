<?php

namespace RTippin\Messenger\Actions;

use RTippin\Messenger\Actions\Base\BaseMessengerAction;
use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\FileService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StoreMessengerAvatar extends BaseMessengerAction
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
     * @var UploadedFile $file $parameters[0]['image']
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $file = $this->upload($parameters[0]['image']);

        $this->removeOldIfExist()
            ->updateProviderAvatar($file);

        return $this;
    }

    /**
     * @param UploadedFile $file
     * @return string
     * @throws HttpException
     */
    private function upload(UploadedFile $file): ?string
    {
        return $this->fileService
            ->setType('image')
            ->setDisk($this->messenger->getAvatarStorage('disk'))
            ->setDirectory($this->getDirectory())
            ->upload($file)
            ->getName();
    }

    /**
     * @return string
     */
    private function getDirectory(): string
    {
        return "{$this->messenger->getAvatarStorage('directory')}/{$this->messenger->getProviderAlias()}/{$this->messenger->getProviderId()}";
    }

    /**
     * @return $this
     */
    private function removeOldIfExist(): self
    {
        if( ! is_null($this->messenger->getProvider()->{$this->messenger->getProvider()->getAvatarColumn()}))
        {
            $this->fileService
                ->setDisk($this->messenger->getAvatarStorage('disk'))
                ->destroy(
                    "{$this->getDirectory()}/{$this->messenger->getProvider()->{$this->messenger->getProvider()->getAvatarColumn()}}"
                );
        }

        return $this;
    }

    /**
     * @param string $file
     */
    private function updateProviderAvatar(string $file): void
    {
        $this->messenger->getProvider()->update([
            $this->messenger->getProvider()->getAvatarColumn() => $file
        ]);
    }
}