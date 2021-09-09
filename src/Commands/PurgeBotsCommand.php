<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Jobs\PurgeBots;
use RTippin\Messenger\Models\Bot;

class PurgeBotsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:purge:bots 
                                            {--now : Perform requested checks now instead of dispatching job}
                                            {--days=30 : Purge threads soft deleted X days ago or greater}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force delete bots X days ago from soft delete';

    /**
     * Execute the console command. We will purge all soft deleted bots
     * that were archived past the set days. We run it through our action
     * to remove the bots directory and sub files from storage, and
     * remove bot from the database.
     *
     * @return void
     */
    public function handle(): void
    {
        $count = Bot::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays($this->option('days')))
            ->count();

        if ($count > 0) {
            Bot::onlyTrashed()
                ->where('deleted_at', '<=', now()->subDays($this->option('days')))
                ->chunk(100, fn (Collection $bots) => $this->dispatchJob($bots));

            $message = $this->option('now') ? 'completed!' : 'dispatched!';

            $this->info("$count bots archived {$this->option('days')} days or greater found. Purging $message");

            return;
        }

        $this->info("No bots archived {$this->option('days')} days or greater found.");
    }

    /**
     * @param  Collection  $bots
     * @return void
     */
    private function dispatchJob(Collection $bots): void
    {
        $this->option('now')
            ? PurgeBots::dispatchSync($bots)
            : PurgeBots::dispatch($bots)->onQueue('messenger');
    }
}
