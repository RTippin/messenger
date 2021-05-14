<?php

namespace RTippin\Messenger\Actions\Messages;

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
     * @param mixed ...$parameters
     * @return $this
     * @throws Throwable|FeatureDisabledException|FileServiceException
     *@var Thread[0]
     * @var AudioMessageRequest[1]
     */
    public function execute(...$parameters): self
    {
        $this->isAudioUploadEnabled();

        $this->setThread($parameters[0])
            ->setMessageType('AUDIO_MESSAGE')
            ->setMessageBody($this->upload($parameters[1]['audio']))
            ->setMessageOptionalParameters($parameters[1])
            ->setMessageOwner($this->messenger->getProvider())
            ->handleTransactions()
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function isAudioUploadEnabled(): void
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
            ->setDirectory($this->getDirectory())
            ->upload($audio);
    }

    /**
     * @return string
     */
    private function getDirectory(): string
    {
        return "{$this->getThread()->getStorageDirectory()}/audio";
    }
}
