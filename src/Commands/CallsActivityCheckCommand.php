<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Jobs\CheckCallsActivity;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Call;

class CallsActivityCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:calls:check-activity 
                                            {--now : Perform requested checks now instead of dispatching job}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check active calls for active participants, end calls with none';

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

        $count = Call::active()->where('created_at', '<', now()->subMinute())->count();
        $message = $this->option('now') ? 'completed' : 'dispatched';

        if ($count > 0) {
            Call::active()
                ->where('created_at', '<', now()->subMinute())
                ->chunk(100, fn (Collection $calls) => $this->dispatchJob($calls));

            $this->info("$count active calls found. Call activity checks $message!");

            return;
        }

        $this->info('No matching active calls found.');
    }

    /**
     * @param  Collection  $calls
     * @return void
     */
    private function dispatchJob(Collection $calls): void
    {
        $this->option('now')
            ? CheckCallsActivity::dispatchSync($calls)
            : CheckCallsActivity::dispatch($calls)->onQueue('messenger');
    }
}
