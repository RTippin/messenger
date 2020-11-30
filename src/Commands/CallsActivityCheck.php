<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Call;

class CallsActivityCheck extends Command
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
     * @param Messenger $messenger
     * @return void
     */
    public function handle(Messenger $messenger): void
    {
        if($messenger->isCallingEnabled() && Call::active()->count())
        {
            Call::active()
                ->where('created_at', '<', now()->subMinute())
                ->chunk(100, fn(Collection $calls) => $this->dispatchJob($calls));

            $this->info('Activity checks dispatched!');
        }
        else
        {
            $this->info('No active calls.');
        }
    }

    /**
     * @param Collection $calls
     */
    private function dispatchJob(Collection $calls)
    {
        $this->option('now')
            ? CheckCallsActivity::dispatchSync($calls)
            : CheckCallsActivity::dispatch($calls)->onQueue('messenger');
    }
}
