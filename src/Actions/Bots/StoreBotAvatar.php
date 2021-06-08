<?php

namespace RTippin\Messenger\Actions\Bots;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\FileServiceException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Services\FileService;
use Throwable;

class StoreBotAvatar extends BotAvatarAction
{
    /**
     * StoreBotAvatar constructor.
     *
     * @param Messenger $messenger
     * @param FileService $fileService
     * @param Dispatcher $dispatcher
     */
    public function __construct(Messenger $messenger,
                                FileService $fileService,
                                Dispatcher $dispatcher)
    {
        parent::__construct(
            $messenger,
            $fileService,
            $dispatcher
        );
    }

    /**
     * @param mixed ...$parameters
     * @var Bot[0]
     * @var UploadedFile[1]['image']
     * @return $this
     * @throws FeatureDisabledException|FileServiceException|Exception
     */
    public function execute(...$parameters): self
    {
        $this->isBotAvatarUploadEnabled();

        $this->setBot($parameters[0]);

        $this->attemptTransactionOrRollbackFile($this->upload($parameters[1]['image']));

        $this->fireEvents();

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
     * @throws FeatureDisabledException
     */
    private function isBotAvatarUploadEnabled(): void
    {
        if (! $this->messenger->isBotsEnabled()
            || ! $this->messenger->isThreadAvatarUploadEnabled()) {
            throw new FeatureDisabledException('Bot Avatar upload is currently disabled.');
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
            ->setDisk($this->getBot()->getStorageDisk())
            ->setDirectory($this->getBot()->getAvatarDirectory())
            ->upload($file);
    }
}