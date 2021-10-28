<?php

namespace RTippin\Messenger\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\Helpers;

class CallRepository
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * CallRepository constructor.
     *
     * @param  Messenger  $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @return Builder
     */
    public function getProviderCallsBuilder(): Builder
    {
        return Call::hasProvider($this->messenger->getProvider())->videoCall();
    }

    /**
     * @return Collection
     */
    public function getProviderAllActiveCalls(): Collection
    {
        return Call::active()->whereIn('thread_id',
            Thread::hasProvider($this->messenger->getProvider())
                ->pluck('id')
                ->toArray()
        )
        ->with(['participants'])
        ->get();
    }

    /**
     * @param  Thread  $thread
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
     * @param  Thread  $thread
     * @param  Call  $call
     * @return Collection
     */
    public function getThreadCallsPage(Thread $thread, Call $call): Collection
    {
        return $thread->calls()
            ->videoCall()
            ->with('owner')
            ->latest()
            ->where('created_at', '<=', Helpers::precisionTime($call->created_at))
            ->where('id', '!=', $call->id)
            ->limit($this->messenger->getCallsPageCount())
            ->get();
    }
}
