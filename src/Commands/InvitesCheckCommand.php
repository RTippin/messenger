<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Jobs\ArchiveInvalidInvites;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Invite;

class InvitesCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:invites:check-valid 
                                            {--now : Perform requested checks now instead of dispatching job}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check active invites for any past expiration or max use cases and invalidate them';

    /**
     * Execute the console command.
     *
     * @param  Messenger  $messenger
     * @return void
     */
    public function handle(Messenger $messenger): void
    {
        // Grab any invites where the expires_at has passed, or where the max_use
        // is not 0 (no limit) and the uses is equal or greater than its max_use
        if (! $messenger->isThreadInvitesEnabled()) {
            $this->info('Thread invites are currently disabled.');

            return;
        }

        $count = Invite::invalid()->count();
        $message = $this->option('now') ? 'completed!' : 'dispatched!';

        if ($count > 0) {
            Invite::invalid()->with('thread')->chunk(100, fn (Collection $invites) => $this->dispatchJob($invites));

            $this->info("$count invalid invites found. Archive invites $message");

            return;
        }

        $this->info('No invalid invites found.');
    }

    /**
     * @param  Collection  $invites
     * @return void
     */
    private function dispatchJob(Collection $invites): void
    {
        $this->option('now')
            ? ArchiveInvalidInvites::dispatchSync($invites)
            : ArchiveInvalidInvites::dispatch($invites)->onQueue('messenger');
    }
}
