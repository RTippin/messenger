<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Models\Thread;

class PurgeThreads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:purge:threads 
                                            {--now : Perform requested checks now instead of dispatching job}
                                            {--days=30 : Purge threads soft deleted X days ago or greater}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force delete threads X days ago from soft delete';

    /**
     * Execute the console command. We will purge all soft deleted threads
     * that were archived past the set days. We run it through our action
     * to remove the entire thread directory and sub files from storage
     * and the thread from the database.
     *
     * @return void
     */
    public function handle(): void
    {
        Thread::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays($this->option('days')))
            ->chunk(100, fn(Collection $threads) => $this->dispatchJob($threads));

        $this->info('Purge threads dispatched.');
    }

    /**
     * @param Collection $threads
     */
    private function dispatchJob(Collection $threads)
    {
        $this->option('now')
            ? PurgeThreadsJob::dispatchSync($threads)
            : PurgeThreadsJob::dispatch($threads)->onQueue('messenger');
    }
}
