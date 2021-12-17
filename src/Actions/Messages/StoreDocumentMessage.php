<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\FileServiceException;
use RTippin\Messenger\Http\Request\DocumentMessageRequest;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\FileService;
use Throwable;

class StoreDocumentMessage extends NewMessageAction
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
     * StoreDocumentMessage constructor.
     *
     * @param  BroadcastDriver  $broadcaster
     * @param  DatabaseManager  $database
     * @param  Dispatcher  $dispatcher
     * @param  Messenger  $messenger
     * @param  FileService  $fileService
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
     * Store / upload new document message, update thread
     * updated_at, mark read for participant, broadcast.
     *
     * @param  Thread  $thread
     * @param  array  $params
     * @param  string|null  $senderIp
     * @return $this
     *
     * @see DocumentMessageRequest
     *
     * @throws Throwable|FeatureDisabledException|FileServiceException
     */
    public function execute(Thread $thread,
                            array $params,
                            ?string $senderIp = null): self
    {
        $this->bailIfDisabled();

        $this->setThread($thread);

        $document = $this->upload($params['document']);

        $this->setMessageType(Message::DOCUMENT_MESSAGE)
            ->setMessageBody($document)
            ->setMessageOptionalParameters($params)
            ->setMessageOwner($this->messenger->getProvider())
            ->setSenderIp($senderIp);

        $this->handleOrRollback($document);

        $this->finalize();

        return $this;
    }

    /**
     * The document file has been uploaded at this point, so if
     * our database actions fail, we want to remove the file
     * from storage and rethrow the exception.
     *
     * @param  string  $fileName
     * @return void
     *
     * @throws Throwable
     */
    private function handleOrRollback(string $fileName): void
    {
        try {
            $this->process();
        } catch (Throwable $e) {
            $this->fileService
                ->setDisk($this->getThread()->getStorageDisk())
                ->destroy("{$this->getThread()->getDocumentsDirectory()}/$fileName");

            throw $e;
        }
    }

    /**
     * @throws FeatureDisabledException
     */
    private function bailIfDisabled(): void
    {
        if (! $this->messenger->isMessageDocumentUploadEnabled()) {
            throw new FeatureDisabledException('Document messages are currently disabled.');
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
            ->setType(FileService::TYPE_DOCUMENT)
            ->setDisk($this->getThread()->getStorageDisk())
            ->setDirectory($this->getThread()->getDocumentsDirectory())
            ->upload($file);
    }
}
