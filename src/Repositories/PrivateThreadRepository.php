<?php

namespace RTippin\Messenger\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\Helpers;

class PrivateThreadRepository
{
    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * @var ThreadRepository
     */
    private ThreadRepository $threadRepository;

    /**
     * PrivateThreadRepository constructor.
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
    public function getProviderPrivateThreadsBuilder(): Builder
    {
        return $this->threadRepository
            ->getProviderThreadsBuilder()
            ->private();
    }

    /**
     * @param MessengerProvider|null $recipient
     * @return Thread|null
     */
    public function getProviderPrivateThreadWithRecipient(MessengerProvider $recipient = null): ?Thread
    {
        if ($this->messenger->isValidMessengerProvider($recipient)) {
            return $this->getProviderPrivateThreadsBuilder()
                ->whereHas('participants',
                fn (Builder $query) => $query->where('owner_id', '=', $recipient->getKey())
                    ->where('owner_type', '=', get_class($recipient))
            )->first();
        }

        return null;
    }

    /**
     * @return Collection
     */
    public function getProviderPrivateThreadsIndex(): Collection
    {
        return $this->getProviderPrivateThreadsBuilder()
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
    public function getProviderPrivateThreadsPage(Thread $thread): Collection
    {
        return $this->getProviderPrivateThreadsBuilder()
            ->latest('updated_at')
            ->with([
                'participants.owner',
                'recentMessage.owner',
                'activeCall.participants.owner',
            ])
            ->where('threads.updated_at', '<=', Helpers::PrecisionTime($thread->updated_at))
            ->where('threads.id', '!=', $thread->id)
            ->limit($this->messenger->getThreadsPageCount())
            ->get();
    }

    /**
     * @return Thread|null
     */
    public function getProviderOldestPrivateThread(): ?Thread
    {
        return $this->getProviderPrivateThreadsBuilder()
            ->oldest('updated_at')
            ->first();
    }
}
