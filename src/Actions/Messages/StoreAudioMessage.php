<?php

namespace RTippin\Messenger\Actions\Messages;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\FileServiceException;
use RTippin\Messenger\Http\Request\AudioMessageRequest;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\FileService;
use Throwable;

class StoreAudioMessage extends NewMessageAction
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
     * StoreAudioMessage constructor.
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
     * Store / upload new audio message, update thread
     * updated_at, mark read for participant, broadcast.
     *
     * @param Thread $thread
     * @param array $params
     * @param string|null $senderIp
     * @return $this
     * @see AudioMessageRequest
     * @throws Throwable|FeatureDisabledException|FileServiceException
     */
    public function execute(Thread $thread,
                            array $params,
                            ?string $senderIp = null): self
    {
        $this->bailWhenFeatureDisabled();

        $this->setThread($thread);

        $audio = $this->upload($params['audio']);

        $this->setMessageType('AUDIO_MESSAGE')
            ->setMessageBody($audio)
            ->setMessageOptionalParameters($params)
            ->setMessageOwner($this->messenger->getProvider())
            ->setSenderIp($senderIp);

        $this->attemptTransactionOrRollbackFile($audio);

        $this->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * The audio file has been uploaded at this point, so if
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
                ->destroy("{$this->getThread()->getAudioDirectory()}/$fileName");

            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws FeatureDisabledException
     */
    private function bailWhenFeatureDisabled(): void
    {
        if (! $this->messenger->isMessageAudioUploadEnabled()) {
            throw new FeatureDisabledException('Audio messages are currently disabled.');
        }
    }

    /**
     * @param UploadedFile $audio
     * @return string
     * @throws FileServiceException
     */
    private function upload(UploadedFile $audio): string
    {
        return $this->fileService
            ->setType('audio')
            ->setDisk($this->getThread()->getStorageDisk())
            ->setDirectory($this->getThread()->getAudioDirectory())
            ->upload($audio);
    }
}
