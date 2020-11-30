<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\Base\BaseMessengerAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Services\Messenger\FileService;

class PurgeDocumentMessages extends BaseMessengerAction
{
    /**
     * @var FileService
     */
    private FileService $fileService;

    /**
     * PurgeDocumentMessages constructor.
     *
     * @param FileService $fileService
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Loop through the collection of document messages and remove
     * the file from storage, then force delete message itself
     * from database
     *
     * @param mixed ...$parameters
     * @var Collection $documents $parameters[0]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        /** @var Collection $documents */

        $documents = $parameters[0];

        $documents->each(
            fn(Message $documents) => $this->purge($documents)
        );

        return $this;
    }

    /**
     * @param Message $documents
     */
    private function purge(Message $documents): void
    {
        $this->destroyImage($documents);

        $this->destroyMessage($documents);
    }

    /**
     * @param Message $documents
     */
    private function destroyImage(Message $documents): void
    {
        $this->fileService
            ->setDisk($documents->getStorageDisk())
            ->destroy($documents->getDocumentPath());
    }

    /**
     * @param Message $documents
     */
    private function destroyMessage(Message $documents): void
    {
        $documents->forceDelete();
    }
}