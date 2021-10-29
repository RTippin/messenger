<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\Calls\CallActivityChecker;
use Throwable;

class CheckCallsActivity extends BaseMessengerJob
{
    /**
     * @var Collection
     */
    public Collection $calls;

    /**
     * Create a new job instance.
     *
     * @param  Collection  $calls
     */
    public function __construct(Collection $calls)
    {
        $this->calls = $calls;
    }

    /**
     * Execute the job.
     *
     * @param  CallActivityChecker  $checker
     * @return void
     *
     * @throws Throwable
     */
    public function handle(CallActivityChecker $checker): void
    {
        $checker->execute($this->calls);
    }
}
