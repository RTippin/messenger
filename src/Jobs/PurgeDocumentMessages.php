<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\Messages\PurgeDocumentMessages as PurgeDocumentMessagesAction;

class PurgeDocumentMessages extends BaseMessengerJob
{
    /**
     * @var Collection
     */
    public Collection $documents;

    /**
     * Create a new job instance.
     *
     * @param  Collection  $documents
     */
    public function __construct(Collection $documents)
    {
        $this->documents = $documents;
    }

    /**
     * Execute the job.
     *
     * @param  PurgeDocumentMessagesAction  $purgeDocuments
     * @return void
     */
    public function handle(PurgeDocumentMessagesAction $purgeDocuments): void
    {
        $purgeDocuments->execute($this->documents);
    }
}
