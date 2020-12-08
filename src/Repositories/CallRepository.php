<?php

namespace RTippin\Messenger\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

class CallRepository
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
     * CallRepository constructor.
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
     * @return Call|Builder
     */
    public function getProviderCallsBuilder()
    {
        return Call::videoCall()->whereHas('participants',
            fn (Builder $query) => $query->where('owner_id', '=', $this->messenger->getProviderId())
                ->where('owner_type', '=', $this->messenger->getProviderClass())
        );
    }

    /**
     * @return Collection
     */
    public function getProviderAllActiveCalls(): Collection
    {
        return Call::active()->whereIn('thread_id',
            $this->threadRepository->getProviderThreadsBuilder()
                ->pluck('id')
                ->toArray()
        )
        ->with(['participants'])
        ->get();
    }

    /**
     * @param Thread $thread
     * @return Collection
     */
    public function getThreadCallsIndex(Thread $thread): Collection
    {
        return $thread->calls()
            ->videoCall()
            ->with('owner')
            ->latest()
            ->limit($this->messenger->getCallsIndexCount())
            ->get();
    }

    /**
     * @param Thread $thread
     * @param Call $call
     * @return Collection
     */
    public function getThreadCallsPage(Thread $thread, Call $call): Collection
    {
        return $thread->calls()
            ->videoCall()
            ->with('owner')
            ->latest()
            ->where('created_at', '<=', $call->created_at)
            ->where('id', '!=', $call->id)
            ->limit($this->messenger->getCallsPageCount())
            ->get();
    }
}
