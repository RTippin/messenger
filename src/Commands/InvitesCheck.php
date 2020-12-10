<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Jobs\ArchiveInvalidInvites;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Invite;

class InvitesCheck extends Command
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
     * @param Messenger $messenger
     * @return void
     */
    public function handle(Messenger $messenger): void
    {
        // Grab any invites where the expires_at has passed, or where the max_use
        // is not 0 (no limit) and the uses is equal or greater than its max_use

        if ($messenger->isThreadInvitesEnabled()) {
            Invite::invalid()->chunk(100, fn (Collection $invites) => $this->dispatchJob($invites));

            $this->info('Invite checks dispatched!');
        }
    }

    /**
     * @param $invites
     */
    private function dispatchJob(Collection $invites)
    {
        $this->option('now')
            ? ArchiveInvalidInvites::dispatchSync($invites)
            : ArchiveInvalidInvites::dispatch($invites)->onQueue('messenger');
    }
}
