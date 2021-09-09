<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Jobs\PurgeAudioMessages;
use RTippin\Messenger\Models\Message;

class PurgeAudioCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:purge:audio 
                                            {--now : Perform requested checks now instead of dispatching job}
                                            {--days=30 : Purge document messages soft deleted X days ago or greater}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force delete document messages X days ago from soft delete';

    /**
     * Execute the console command. We will purge all soft deleted document
     * messages that were archived past the set days. We run it through
     * our action to remove the file from storage and message from
     * database.
     *
     * @return void
     */
    public function handle(): void
    {
        $count = Message::audio()
            ->onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays($this->option('days')))
            ->count();

        if ($count > 0) {
            Message::audio()
                ->onlyTrashed()
                ->where('deleted_at', '<=', now()->subDays($this->option('days')))
                ->chunk(100, fn (Collection $audioFiles) => $this->dispatchJob($audioFiles));

            $message = $this->option('now') ? 'completed!' : 'dispatched!';

            $this->info("$count audio messages archived {$this->option('days')} days or greater found. Purging $message");

            return;
        }

        $this->info("No audio messages archived {$this->option('days')} days or greater found.");
    }

    /**
     * @param  Collection  $audioFiles
     * @return void
     */
    private function dispatchJob(Collection $audioFiles): void
    {
        $this->option('now')
            ? PurgeAudioMessages::dispatchSync($audioFiles)
            : PurgeAudioMessages::dispatch($audioFiles)->onQueue('messenger');
    }
}
