<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Jobs\EndCalls;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Call;

class CallsDownCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:calls:down 
                                            {--duration=30 : Minutes to keep the calling disabled}
                                            {--now : End all active calls now instead of dispatching jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'End all active calls and disable the calling system for the specified minutes.';

    /**
     * Execute the console command.
     *
     * @param  Messenger  $messenger
     * @return void
     */
    public function handle(Messenger $messenger): void
    {
        if (! $messenger->isCallingEnabled()) {
            $this->info('Call system currently disabled.');

            return;
        }

        $messenger->disableCallsTemporarily($this->option('duration'));

        $count = Call::active()->count();
        $message = $this->option('now') ? 'completed' : 'dispatched';

        if ($count > 0) {
            Call::active()->chunk(100, fn (Collection $calls) => $this->dispatchJob($calls));

            $this->info("$count active calls found. End calls $message!");
        } else {
            $this->info('No active calls to end found.');
        }

        $this->info("Call system is now down for {$this->option('duration')} minutes.");
    }

    /**
     * @param  Collection  $calls
     * @return void
     */
    private function dispatchJob(Collection $calls): void
    {
        $this->option('now')
            ? EndCalls::dispatchSync($calls)
            : EndCalls::dispatch($calls)->onQueue('messenger');
    }
}
