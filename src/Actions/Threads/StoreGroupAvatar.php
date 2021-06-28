<?php

namespace RTippin\Messenger\Actions\Threads;

use Exception;
use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\FileServiceException;
use RTippin\Messenger\Http\Request\GroupAvatarRequest;
use RTippin\Messenger\Models\Thread;
use Throwable;

class StoreGroupAvatar extends GroupAvatarAction
{
    /**
     * @param mixed ...$parameters
     * @var Thread[0]
     * @var GroupAvatarRequest[1]
     * @return $this
     * @throws FeatureDisabledException|FileServiceException|Exception
     */
    public function execute(...$parameters): self
    {
        $this->isThreadAvatarsEnabled();

        $this->setThread($parameters[0])
            ->attemptTransactionOrRollbackFile($this->uploadAvatar($parameters[1]['image']))
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * The avatar has been uploaded at this point, so if our
     * database actions fail, we want to remove the avatar
     * from storage and rethrow the exception.
     *
     * @param string $fileName
     * @return $this
     * @throws Exception
     */
    private function attemptTransactionOrRollbackFile(string $fileName): self
    {
        try {
            return $this->removeOldIfExist()->updateGroupAvatar($fileName);
        } catch (Throwable $e) {
            $this->fileService
                ->setDisk($this->getThread()->getStorageDisk())
                ->destroy("{$this->getThread()->getAvatarDirectory()}/$fileName");

            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param UploadedFile $image
     * @return string|null
     * @throws FileServiceException
     */
    private function uploadAvatar(UploadedFile $image): string
    {
        return $this->fileService
            ->setType('image')
            ->setDisk($this->getThread()->getStorageDisk())
            ->setDirectory($this->getThread()->getAvatarDirectory())
            ->upload($image);
    }
}
