<?php

namespace RTippin\Messenger\Actions\Messages;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\FileServiceException;
use RTippin\Messenger\Http\Request\ImageMessageRequest;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\FileService;
use Throwable;

class StoreImageMessage extends NewMessageAction
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var FileService
     */
    private FileService $fileService;

    /**
     * StoreImageMessage constructor.
     *
     * @param BroadcastDriver $broadcaster
     * @param DatabaseManager $database
     * @param Dispatcher $dispatcher
     * @param Messenger $messenger
     * @param FileService $fileService
     */
    public function __construct(BroadcastDriver $broadcaster,
                                DatabaseManager $database,
                                Dispatcher $dispatcher,
                                Messenger $messenger,
                                FileService $fileService)
    {
        parent::__construct(
            $broadcaster,
            $database,
            $dispatcher
        );

        $this->messenger = $messenger;
        $this->fileService = $fileService;
    }

    /**
     * Store / upload new image message, update thread
     * updated_at, mark read for participant, broadcast.
     *
     * @param mixed ...$parameters
     * @var Thread[0]
     * @var ImageMessageRequest[1]
     * @var string|null[2]
     * @return $this
     * @throws Throwable|FeatureDisabledException|FileServiceException
     */
    public function execute(...$parameters): self
    {
        $this->isImageUploadEnabled();

        $this->setThread($parameters[0]);

        $image = $this->upload($parameters[1]['image']);

        $this->setMessageType('IMAGE_MESSAGE')
            ->setMessageBody($image)
            ->setMessageOptionalParameters($parameters[1])
            ->setMessageOwner($this->messenger->getProvider())
            ->setSenderIp($parameters[2] ?? null);

        $this->attemptTransactionOrRollbackFile($image);

        $this->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * The image file has been uploaded at this point, so if
     * our database actions fail, we want to remove the file
     * from storage and rethrow the exception.
     *
     * @param string $fileName
     * @throws Exception
     */
    private function attemptTransactionOrRollbackFile(string $fileName): void
    {
        try {
            $this->handleTransactions();
        } catch (Throwable $e) {
            $this->fileService
                ->setDisk($this->getThread()->getStorageDisk())
                ->destroy("{$this->getThread()->getImagesDirectory()}/$fileName");

            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws FeatureDisabledException
     */
    private function isImageUploadEnabled(): void
    {
        if (! $this->messenger->isMessageImageUploadEnabled()) {
            throw new FeatureDisabledException('Image messages are currently disabled.');
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
            ->setDisk($this->getThread()->getStorageDisk())
            ->setDirectory($this->getThread()->getImagesDirectory())
            ->upload($file);
    }
}
