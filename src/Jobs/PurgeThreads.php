<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Threads\PurgeThreads as PurgeThreadsAction;

class PurgeThreads implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var Collection
     */
    private Collection $threads;

    /**
     * Create a new job instance.
     *
     * @param Collection $threads
     */
    public function __construct(Collection $threads)
    {
        $this->threads = $threads;
    }

    /**
     * Execute the job.
     *
     * @param PurgeThreadsAction $purgeThreads
     * @return void
     */
    public function handle(PurgeThreadsAction $purgeThreads): void
    {
        $purgeThreads->execute($this->threads);
    }
}
