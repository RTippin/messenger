<?php

namespace RTippin\Messenger\Actions\Messenger;

use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\UploadFailedException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Services\FileService;

class StoreMessengerAvatar extends MessengerAvatarAction
{
    /**
     * StoreMessengerAvatar constructor.
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
     * @var UploadedFile[0]['image']
     * @return $this
     * @throws FeatureDisabledException|UploadFailedException
     */
    public function execute(...$parameters): self
    {
        $this->isAvatarUploadEnabled();

        $file = $this->upload($parameters[0]['image']);

        $this->removeOldIfExist()->updateProviderAvatar($file);

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function isAvatarUploadEnabled(): void
    {
        if (! $this->messenger->isProviderAvatarUploadEnabled()) {
            throw new FeatureDisabledException('Avatar upload is currently disabled.');
        }
    }

    /**
     * @param UploadedFile $file
     * @return string
     * @throws UploadFailedException
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
}
