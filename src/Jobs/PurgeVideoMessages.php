<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\Messages\PurgeVideoMessages as PurgeVideoMessagesAction;

class PurgeVideoMessages extends BaseMessengerJob
{
    /**
     * @var Collection
     */
    public Collection $videoFiles;

    /**
     * Create a new job instance.
     *
     * @param  Collection  $videoFiles
     */
    public function __construct(Collection $videoFiles)
    {
        $this->videoFiles = $videoFiles;
    }

    /**
     * Execute the job.
     *
     * @param  PurgeVideoMessagesAction  $purgeVideo
     * @return void
     */
    public function handle(PurgeVideoMessagesAction $purgeVideo): void
    {
        $purgeVideo->execute($this->videoFiles);
    }
}
