<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Jobs\PurgeVideoMessages;
use RTippin\Messenger\Models\Message;

class PurgeVideosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:purge:videos 
                                            {--now : Perform requested checks now instead of dispatching job}
                                            {--days=30 : Purge video messages soft deleted X days ago or greater}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force delete video messages X days ago from soft delete';

    /**
     * Execute the console command. We will purge all soft deleted video
     * messages that were archived past the set days. We run it through
     * our action to remove the video from storage and message from
     * database.
     *
     * @return void
     */
    public function handle(): void
    {
        $count = Message::video()
            ->onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays($this->option('days')))
            ->count();

        if ($count > 0) {
            Message::video()
                ->onlyTrashed()
                ->where('deleted_at', '<=', now()->subDays($this->option('days')))
                ->chunk(100, fn (Collection $videoFiles) => $this->dispatchJob($videoFiles));

            $message = $this->option('now') ? 'completed!' : 'dispatched!';

            $this->info("$count video messages archived {$this->option('days')} days or greater found. Purging $message");

            return;
        }

        $this->info("No video messages archived {$this->option('days')} days or greater found.");
    }

    /**
     * @param  Collection  $videoFiles
     * @return void
     */
    private function dispatchJob(Collection $videoFiles): void
    {
        $this->option('now')
            ? PurgeVideoMessages::dispatchSync($videoFiles)
            : PurgeVideoMessages::dispatch($videoFiles)->onQueue('messenger');
    }
}
