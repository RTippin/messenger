<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Jobs\PurgeThreads;
use RTippin\Messenger\Models\Thread;

class PurgeThreadsCommand extends Command
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
        $count = Thread::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays($this->option('days')))
            ->count();

        if ($count > 0) {
            Thread::onlyTrashed()
                ->where('deleted_at', '<=', now()->subDays($this->option('days')))
                ->chunk(100, fn (Collection $threads) => $this->dispatchJob($threads));

            $message = $this->option('now') ? 'completed!' : 'dispatched!';

            $this->info("$count threads archived {$this->option('days')} days or greater found. Purging $message");

            return;
        }

        $this->info("No threads archived {$this->option('days')} days or greater found.");
    }

    /**
     * @param  Collection  $threads
     * @return void
     */
    private function dispatchJob(Collection $threads): void
    {
        $this->option('now')
            ? PurgeThreads::dispatchSync($threads)
            : PurgeThreads::dispatch($threads)->onQueue('messenger');
    }
}
