<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Messages\PurgeDocumentMessages as PurgeDocumentMessagesAction;

class PurgeDocumentMessages implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var Collection
     */
    private Collection $documents;

    /**
     * Create a new job instance.
     *
     * @param Collection $documents
     */
    public function __construct(Collection $documents)
    {
        $this->documents = $documents;
    }

    /**
     * Execute the job.
     *
     * @param PurgeDocumentMessagesAction $purgeDocuments
     * @return void
     */
    public function handle(PurgeDocumentMessagesAction $purgeDocuments): void
    {
        $purgeDocuments->execute($this->documents);
    }
}
