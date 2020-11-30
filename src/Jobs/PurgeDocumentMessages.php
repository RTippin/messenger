<?php

namespace RTippin\Messenger\Jobs;

use RTippin\Messenger\Actions\Messages\PurgeDocumentMessages as PurgeDocumentMessagesAction;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PurgeDocumentMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Collection
     */
    private Collection $documents;

    /**
     * Create a new job instance.
     *
     * @param $documents
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
    public function handle(PurgeDocumentMessagesAction $purgeDocuments)
    {
        $purgeDocuments->execute($this->documents);
    }
}