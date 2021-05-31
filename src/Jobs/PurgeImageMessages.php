<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Messages\PurgeImageMessages as PurgeImageMessagesAction;

class PurgeImageMessages implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var Collection
     */
    private Collection $images;

    /**
     * Create a new job instance.
     *
     * @param Collection $images
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
    public function handle(PurgeImageMessagesAction $purgeImages): void
    {
        $purgeImages->execute($this->images);
    }
}
