<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Messages\PurgeAudioMessages as PurgeAudioMessagesAction;

class PurgeAudioMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Collection
     */
    private Collection $audioFiles;

    /**
     * Create a new job instance.
     *
     * @param $audioFiles
     */
    public function __construct(Collection $audioFiles)
    {
        $this->audioFiles = $audioFiles;
    }

    /**
     * Execute the job.
     *
     * @param PurgeAudioMessagesAction $purgeAudio
     * @return void
     */
    public function handle(PurgeAudioMessagesAction $purgeAudio)
    {
        $purgeAudio->execute($this->audioFiles);
    }
}
