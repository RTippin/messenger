<?php

namespace RTippin\Messenger\Jobs;

use RTippin\Messenger\Actions\Threads\PurgeThreads as PurgeThreadsAction;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PurgeThreads implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Collection
     */
    private Collection $threads;

    /**
     * Create a new job instance.
     *
     * @param $threads
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
    public function handle(PurgeThreadsAction $purgeThreads)
    {
        $purgeThreads->execute($this->threads);
    }
}