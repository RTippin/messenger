<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\Base\BaseMessengerAction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\FileService;

class PurgeThreads extends BaseMessengerAction
{
    /**
     * @var FileService
     */
    private FileService $fileService;

    /**
     * PurgeThreads constructor.
     *
     * @param FileService $fileService
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Loop through the collection of image messages and remove
     * the image from storage, then force delete message itself
     * from database
     *
     * @param mixed ...$parameters
     * @var Collection $threads $parameters[0]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        /** @var Collection $threads */

        $threads = $parameters[0];

        $threads->each(
            fn(Thread $thread) => $this->purge($thread)
        );

        return $this;
    }

    /**
     * @param Thread $thread
     */
    private function purge(Thread $thread): void
    {
        $this->destroyDirectory($thread);

        $this->destroyThread($thread);
    }

    /**
     * @param Thread $thread
     */
    private function destroyDirectory(Thread $thread): void
    {
        $this->fileService
            ->setDisk($thread->getStorageDisk())
            ->setDirectory($thread->getStorageDirectory())
            ->destroyDirectory();
    }

    /**
     * @param Thread $thread
     */
    private function destroyThread(Thread $thread): void
    {
        $thread->forceDelete();
    }
}