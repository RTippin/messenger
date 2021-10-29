<?php

namespace RTippin\Messenger\Repositories;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\Helpers;

class GroupThreadRepository
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * GroupThreadRepository constructor.
     *
     * @param  Messenger  $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @return Collection
     */
    public function getProviderGroupThreadsIndex(): Collection
    {
        return Thread::hasProvider($this->messenger->getProvider())
            ->group()
            ->latest('updated_at')
            ->with([
                'participants.owner',
                'latestMessage.owner',
                'activeCall.participants.owner',
            ])
            ->limit($this->messenger->getThreadsIndexCount())
            ->get();
    }

    /**
     * @param  Thread  $thread
     * @return Collection
     */
    public function getProviderGroupThreadsPage(Thread $thread): Collection
    {
        return Thread::hasProvider($this->messenger->getProvider())
            ->group()
            ->latest('updated_at')
            ->with([
                'participants.owner',
                'latestMessage.owner',
                'activeCall.participants.owner',
            ])
            ->where('threads.updated_at', '<=', Helpers::precisionTime($thread->updated_at))
            ->where('threads.id', '!=', $thread->id)
            ->limit($this->messenger->getThreadsPageCount())
            ->get();
    }

    /**
     * @return Thread|null
     */
    public function getProviderOldestGroupThread(): ?Thread
    {
        return Thread::hasProvider($this->messenger->getProvider())
            ->group()
            ->oldest('updated_at')
            ->first();
    }
}
