<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Calls\CallActivityChecker;
use Throwable;

class CheckCallsActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Collection
     */
    private Collection $calls;

    /**
     * Create a new job instance.
     *
     * @param $calls
     */
    public function __construct(Collection $calls)
    {
        $this->calls = $calls;
    }

    /**
     * Execute the job.
     *
     * @param CallActivityChecker $checker
     * @return void
     * @throws Throwable
     */
    public function handle(CallActivityChecker $checker)
    {
        $checker->execute($this->calls);
    }
}
