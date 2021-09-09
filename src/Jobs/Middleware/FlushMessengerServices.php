<?php

namespace RTippin\Messenger\Jobs\Middleware;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;

class FlushMessengerServices
{
    /**
     * Process the queued job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, callable $next)
    {
        Messenger::flush();
        MessengerBots::flush();

        return $next($job);
    }
}
