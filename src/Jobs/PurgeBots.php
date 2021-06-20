<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Bots\PurgeBots as PurgeBotsAction;

class PurgeBots implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var Collection
     */
    private Collection $bots;

    /**
     * Create a new job instance.
     *
     * @param Collection $bots
     */
    public function __construct(Collection $bots)
    {
        $this->bots = $bots;
    }

    /**
     * Execute the job.
     *
     * @param PurgeBotsAction $purgeBots
     * @return void
     */
    public function handle(PurgeBotsAction $purgeBots): void
    {
        $purgeBots->execute($this->bots);
    }
}
