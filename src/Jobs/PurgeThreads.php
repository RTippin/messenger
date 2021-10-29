<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\Threads\PurgeThreads as PurgeThreadsAction;

class PurgeThreads extends BaseMessengerJob
{
    /**
     * @var Collection
     */
    public Collection $threads;

    /**
     * Create a new job instance.
     *
     * @param  Collection  $threads
     */
    public function __construct(Collection $threads)
    {
        $this->threads = $threads;
    }

    /**
     * Execute the job.
     *
     * @param  PurgeThreadsAction  $purgeThreads
     * @return void
     */
    public function handle(PurgeThreadsAction $purgeThreads): void
    {
        $purgeThreads->execute($this->threads);
    }
}
