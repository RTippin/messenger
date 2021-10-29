<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\Bots\PurgeBots as PurgeBotsAction;

class PurgeBots extends BaseMessengerJob
{
    /**
     * @var Collection
     */
    public Collection $bots;

    /**
     * Create a new job instance.
     *
     * @param  Collection  $bots
     */
    public function __construct(Collection $bots)
    {
        $this->bots = $bots;
    }

    /**
     * Execute the job.
     *
     * @param  PurgeBotsAction  $purgeBots
     * @return void
     */
    public function handle(PurgeBotsAction $purgeBots): void
    {
        $purgeBots->execute($this->bots);
    }
}
