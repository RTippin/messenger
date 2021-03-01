<?php

namespace RTippin\Messenger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Actions\Calls\EndCall;
use RTippin\Messenger\Models\Call;
use Throwable;

class EndCalls implements ShouldQueue
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
     * @param EndCall $endCall
     * @return void
     * @throws Throwable|InvalidArgumentException
     */
    public function handle(EndCall $endCall)
    {
        $this->calls->each(fn (Call $call) => $endCall->execute($call));
    }
}
