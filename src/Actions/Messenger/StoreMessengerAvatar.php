<?php

namespace RTippin\Messenger\Actions\Messenger;

use Exception;
use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\FileServiceException;
use RTippin\Messenger\Http\Request\MessengerAvatarRequest;
use RTippin\Messenger\Services\FileService;
use Throwable;

class StoreMessengerAvatar extends MessengerAvatarAction
{
    /**
     * @param  UploadedFile  $image
     * @return $this
     *
     * @see MessengerAvatarRequest
     *
     * @throws FeatureDisabledException|FileServiceException|Exception
     */
    public function execute(UploadedFile $image): self
    {
        $this->bailIfDisabled();

        $this->handleOrRollback(
            $this->upload($image)
        );

        return $this;
    }

    /**
     * The avatar has been uploaded at this point, so if our
     * database actions fail, we want to remove the avatar
     * from storage and rethrow the exception.
     *
     * @param  string  $fileName
     * @return void
     *
     * @throws Exception
     */
    private function handleOrRollback(string $fileName): void
    {
        try {
            $this->removeOldIfExist()->updateProviderAvatar($fileName);
        } catch (Throwable $e) {
            $this->fileService
                ->setDisk($this->messenger->getAvatarStorage('disk'))
                ->destroy("{$this->getDirectory()}/$fileName");

            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws FeatureDisabledException
     */
    private function bailIfDisabled(): void
    {
        if (! $this->messenger->isProviderAvatarEnabled()) {
            throw new FeatureDisabledException('Avatar upload is currently disabled.');
        }
    }

    /**
     * @param  UploadedFile  $file
     * @return string
     *
     * @throws FileServiceException
     */
    private function upload(UploadedFile $file): string
    {
        return $this->fileService
            ->setType(FileService::TYPE_IMAGE)
            ->setDisk($this->messenger->getAvatarStorage('disk'))
            ->setDirectory($this->getDirectory())
            ->upload($file);
    }
}
