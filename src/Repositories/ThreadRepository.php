<?php

namespace RTippin\Messenger\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;

class ThreadRepository
{
    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * ThreadRepository constructor.
     *
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @return Thread|Builder
     */
    public function getProviderThreadsBuilder(): Builder
    {
        return Thread::whereHas('participants',
            fn (Builder $query) => $query->where('owner_id', '=', $this->messenger->getProviderId())
                ->where('owner_type', '=', $this->messenger->getProviderClass())
        );
    }

    /**
     * @return Collection
     */
    public function getProviderThreadsIndex(): Collection
    {
        return $this->getProviderThreadsBuilder()
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
    public function getProviderThreadsPage(Thread $thread): Collection
    {
        return $this->getProviderThreadsBuilder()
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
     * @return Thread|Builder
     */
    public function getProviderThreadsWithActiveCallsBuilder(): Builder
    {
        return $this->getProviderThreadsBuilder()
            ->has('activeCall');
    }

    /**
     * @return Collection
     */
    public function getProviderThreadsWithActiveCalls(): Collection
    {
        return $this->getProviderThreadsWithActiveCallsBuilder()
            ->get();
    }

    /**
     * @return Thread|null
     */
    public function getProviderOldestThread(): ?Thread
    {
        return $this->getProviderThreadsBuilder()
            ->oldest('updated_at')
            ->first();
    }

    /**
     * @return Thread|Builder
     */
    public function getProviderUnreadThreadsBuilder(): Builder
    {
        return Thread::whereHas('participants',
            function (Builder $query) {
                $query->where('owner_id', '=', $this->messenger->getProviderId())
                    ->where('owner_type', '=', $this->messenger->getProviderClass())
                    ->where(function (Builder $query) {
                        $query->whereNull('participants.last_read');
                        $query->orWhere('threads.updated_at', '>', $query->raw('participants.last_read'));
                    });
            }
        );
    }
}
