<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\Messages\PurgeAudioMessages as PurgeAudioMessagesAction;

class PurgeAudioMessages extends BaseMessengerJob
{
    /**
     * @var Collection
     */
    public Collection $audioFiles;

    /**
     * Create a new job instance.
     *
     * @param  Collection  $audioFiles
     */
    public function __construct(Collection $audioFiles)
    {
        $this->audioFiles = $audioFiles;
    }

    /**
     * Execute the job.
     *
     * @param  PurgeAudioMessagesAction  $purgeAudio
     * @return void
     */
    public function handle(PurgeAudioMessagesAction $purgeAudio): void
    {
        $purgeAudio->execute($this->audioFiles);
    }
}
