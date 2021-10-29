<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\Messages\PurgeImageMessages as PurgeImageMessagesAction;

class PurgeImageMessages extends BaseMessengerJob
{
    /**
     * @var Collection
     */
    public Collection $images;

    /**
     * Create a new job instance.
     *
     * @param  Collection  $images
     */
    public function __construct(Collection $images)
    {
        $this->images = $images;
    }

    /**
     * Execute the job.
     *
     * @param  PurgeImageMessagesAction  $purgeImages
     * @return void
     */
    public function handle(PurgeImageMessagesAction $purgeImages): void
    {
        $purgeImages->execute($this->images);
    }
}
