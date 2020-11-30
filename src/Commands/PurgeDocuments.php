<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Jobs\PurgeDocumentMessages;
use RTippin\Messenger\Models\Message;

class PurgeDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:purge:documents 
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
        Message::document()
            ->onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays($this->option('days')))
            ->chunk(100, fn(Collection $images) => $this->dispatchJob($images));

        $this->info('Purge document messages dispatched.');
    }

    /**
     * @param Collection $images
     */
    private function dispatchJob(Collection $images)
    {
        $this->option('now')
            ? PurgeDocumentMessages::dispatchSync($images)
            : PurgeDocumentMessages::dispatch($images)->onQueue('messenger');
    }
}
