<?php

namespace RTippin\Messenger\Actions\Messenger;

use Exception;
use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\FileServiceException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Services\FileService;
use Throwable;

class StoreMessengerAvatar extends MessengerAvatarAction
{
    /**
     * StoreMessengerAvatar constructor.
     *
     * @param Messenger $messenger
     * @param FileService $fileService
     */
    public function __construct(Messenger $messenger, FileService $fileService)
    {
        parent::__construct($messenger, $fileService);
    }

    /**
     * @param mixed ...$parameters
     * @return $this
     * @throws FeatureDisabledException|FileServiceException|Exception
     * @var UploadedFile[0]['image']
     */
    public function execute(...$parameters): self
    {
        $this->isAvatarUploadEnabled();

        $this->attemptTransactionOrRollbackFile($this->upload($parameters[0]['image']));

        return $this;
    }

    /**
     * The avatar has been uploaded at this point, so if our
     * database actions fail, we want to remove the avatar
     * from storage and rethrow the exception.
     *
     * @param string $fileName
     * @throws Exception
     */
    private function attemptTransactionOrRollbackFile(string $fileName): void
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
    private function isAvatarUploadEnabled(): void
    {
        if (! $this->messenger->isProviderAvatarUploadEnabled()) {
            throw new FeatureDisabledException('Avatar upload is currently disabled.');
        }
    }

    /**
     * @param UploadedFile $file
     * @return string
     * @throws FileServiceException
     */
    private function upload(UploadedFile $file): string
    {
        return $this->fileService
            ->setType('image')
            ->setDisk($this->messenger->getAvatarStorage('disk'))
            ->setDirectory($this->getDirectory())
            ->upload($file);
    }
}
