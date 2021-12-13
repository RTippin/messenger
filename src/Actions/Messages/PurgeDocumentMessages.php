<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Services\FileService;

class PurgeDocumentMessages extends BaseMessengerAction
{
    /**
     * @var FileService
     */
    private FileService $fileService;

    /**
     * PurgeDocumentMessages constructor.
     *
     * @param  FileService  $fileService
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Loop through the collection of document messages and remove
     * the file from storage, then force delete message itself
     * from database.
     *
     * @param  Collection  $documentMessages
     * @return $this
     */
    public function execute(Collection $documentMessages): self
    {
        $documentMessages->each(fn (Message $document) => $this->purge($document));

        return $this;
    }

    /**
     * @param  Message  $document
     * @return void
     */
    private function purge(Message $document): void
    {
        $this->destroyDocument($document);

        $this->destroyMessage($document);
    }

    /**
     * @param  Message  $document
     * @return void
     */
    private function destroyDocument(Message $document): void
    {
        $this->fileService
            ->setDisk($document->getStorageDisk())
            ->destroy($document->getDocumentPath());
    }

    /**
     * @param  Message  $document
     * @return void
     */
    private function destroyMessage(Message $document): void
    {
        $document->forceDelete();
    }
}
