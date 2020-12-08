<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Jobs\PurgeImageMessages;
use RTippin\Messenger\Models\Message;

class PurgeImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:purge:images 
                                            {--now : Perform requested checks now instead of dispatching job}
                                            {--days=30 : Purge image messages soft deleted X days ago or greater}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force delete image messages X days ago from soft delete';

    /**
     * Execute the console command. We will purge all soft deleted image
     * messages that were archived past the set days. We run it through
     * our action to remove the image from storage and message from
     * database.
     *
     * @return void
     */
    public function handle(): void
    {
        Message::image()
            ->onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays($this->option('days')))
            ->chunk(100, fn (Collection $images) => $this->dispatchJob($images));

        $this->info('Purge image messages dispatched.');
    }

    /**
     * @param Collection $images
     */
    private function dispatchJob(Collection $images)
    {
        $this->option('now')
            ? PurgeImageMessages::dispatchSync($images)
            : PurgeImageMessages::dispatch($images)->onQueue('messenger');
    }
}
