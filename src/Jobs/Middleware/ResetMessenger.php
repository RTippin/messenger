<?php

namespace RTippin\Messenger\Jobs\Middleware;

use RTippin\Messenger\Facades\Messenger;

class ResetMessenger
{
    /**
     * Process the queued job.
     *
     * @param mixed $job
     * @param callable $next
     * @return mixed
     */
    public function handle($job, callable $next)
    {
        Messenger::flush();

        return $next($job);
    }
}
