<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\FileService;

class PurgeThreads extends BaseMessengerAction
{
    /**
     * @var FileService
     */
    private FileService $fileService;

    /**
     * PurgeThreads constructor.
     *
     * @param  FileService  $fileService
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Loop through the collection of threads and remove all files
     * from storage under the thread, then force delete thread
     * itself from database.
     *
     * @param  Collection  $threads
     * @return $this
     */
    public function execute(Collection $threads): self
    {
        $threads->each(fn (Thread $thread) => $this->purge($thread));

        return $this;
    }

    /**
     * @param  Thread  $thread
     * @return void
     */
    private function purge(Thread $thread): void
    {
        $this->destroyDirectory($thread);

        $this->destroyThread($thread);
    }

    /**
     * @param  Thread  $thread
     * @return void
     */
    private function destroyDirectory(Thread $thread): void
    {
        $this->fileService
            ->setDisk($thread->getStorageDisk())
            ->setDirectory($thread->getStorageDirectory())
            ->destroyDirectory();
    }

    /**
     * @param  Thread  $thread
     * @return void
     */
    private function destroyThread(Thread $thread): void
    {
        $thread->forceDelete();
    }
}
