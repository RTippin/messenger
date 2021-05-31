<?php

namespace RTippin\Messenger\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Actions\Invites\ArchiveInvite;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Models\Invite;

class ArchiveInvalidInvites implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var Collection
     */
    private Collection $invites;

    /**
     * Create a new job instance.
     *
     * @param Collection $invites
     */
    public function __construct(Collection $invites)
    {
        $this->invites = $invites;
    }

    /**
     * Execute the job.
     *
     * @param ArchiveInvite $archiveInvite
     * @return void
     * @throws Exception|FeatureDisabledException
     */
    public function handle(ArchiveInvite $archiveInvite): void
    {
        $this->invites
            ->reject(fn (Invite $invite) => $invite->isValid())
            ->each(fn (Invite $invite) => $archiveInvite->execute($invite));
    }
}
