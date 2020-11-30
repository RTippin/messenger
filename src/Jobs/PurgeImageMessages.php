<?php

namespace RTippin\Messenger\Jobs;

use RTippin\Messenger\Actions\Messages\PurgeImageMessages as PurgeImageMessagesAction;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PurgeImageMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Collection
     */
    private Collection $images;

    /**
     * Create a new job instance.
     *
     * @param $images
     */
    public function __construct(Collection $images)
    {
        $this->images = $images;
    }

    /**
     * Execute the job.
     *
     * @param PurgeImageMessagesAction $purgeImages
     * @return void
     */
    public function handle(PurgeImageMessagesAction $purgeImages)
    {
        $purgeImages->execute($this->images);
    }
}