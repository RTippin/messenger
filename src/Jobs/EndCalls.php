<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\Calls\EndCall;
use RTippin\Messenger\Models\Call;
use Throwable;

class EndCalls extends BaseMessengerJob
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
     * @param  EndCall  $endCall
     * @return void
     *
     * @throws Throwable
     */
    public function handle(EndCall $endCall): void
    {
        $this->calls->each(fn (Call $call) => $endCall->execute($call));
    }
}
