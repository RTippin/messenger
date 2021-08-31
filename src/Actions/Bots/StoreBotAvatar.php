<?php

namespace RTippin\Messenger\Actions\Bots;

use Exception;
use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\FileServiceException;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Services\FileService;
use Throwable;

class StoreBotAvatar extends BotAvatarAction
{
    /**
     * @param Bot $bot
     * @param UploadedFile $image
     * @return $this
     * @throws FeatureDisabledException|FileServiceException|Exception
     */
    public function execute(Bot $bot, UploadedFile $image): self
    {
        $this->bailWhenFeatureDisabled();

        $this->setBot($bot);

        $this->attemptTransactionOrRollbackFile($this->upload($image));

        $this->generateResource()->fireEvents();

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
            $this->removeOldIfExist()->updateBotAvatar($fileName);
        } catch (Throwable $e) {
            $this->fileService
                ->setDisk($this->getBot()->getStorageDisk())
                ->destroy("{$this->getBot()->getAvatarDirectory()}/$fileName");

            throw new Exception($e->getMessage(), $e->getCode(), $e);
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
            ->setType(FileService::TYPE_IMAGE)
            ->setDisk($this->getBot()->getStorageDisk())
            ->setDirectory($this->getBot()->getAvatarDirectory())
            ->upload($file);
    }
}
