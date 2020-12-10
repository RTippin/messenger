<?php

namespace RTippin\Messenger\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;

class GroupThreadRepository
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var ThreadRepository
     */
    private ThreadRepository $threadRepository;

    /**
     * GroupThreadRepository constructor.
     *
     * @param Messenger $messenger
     * @param ThreadRepository $threadRepository
     */
    public function __construct(Messenger $messenger,
                                ThreadRepository $threadRepository)
    {
        $this->messenger = $messenger;
        $this->threadRepository = $threadRepository;
    }

    /**
     * @return Thread|Builder
     */
    public function getProviderGroupThreadsBuilder(): Builder
    {
        return $this->threadRepository
            ->getProviderThreadsBuilder()
            ->group();
    }

    /**
     * @return Collection
     */
    public function getProviderGroupThreadsIndex(): Collection
    {
        return $this->getProviderGroupThreadsBuilder()
            ->latest('updated_at')
            ->with([
                'participants.owner',
                'recentMessage.owner',
                'activeCall.participants.owner',
            ])
            ->limit($this->messenger->getThreadsIndexCount())
            ->get();
    }

    /**
     * @param Thread $thread
     * @return Collection
     */
    public function getProviderGroupThreadsPage(Thread $thread): Collection
    {
        return $this->getProviderGroupThreadsBuilder()
            ->latest('updated_at')
            ->with([
                'participants.owner',
                'recentMessage.owner',
                'activeCall.participants.owner',
            ])
            ->where('threads.updated_at', '<=', $thread->updated_at)
            ->where('threads.id', '!=', $thread->id)
            ->limit($this->messenger->getThreadsPageCount())
            ->get();
    }

    /**
     * @return Thread|null
     */
    public function getProviderOldestGroupThread(): ?Thread
    {
        return $this->getProviderGroupThreadsBuilder()
            ->oldest('updated_at')
            ->first();
    }
}
