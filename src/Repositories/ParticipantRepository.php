<?php

namespace RTippin\Messenger\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\Helpers;

class ParticipantRepository
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * ParticipantRepository constructor.
     *
     * @param  Messenger  $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * Get all participants who pass our requirements for broadcasting.
     *
     * @param  Thread  $thread
     * @param  bool  $withoutProvider
     * @return Collection
     */
    public function getThreadBroadcastableParticipants(Thread $thread, bool $withoutProvider = false): Collection
    {
        return $thread->participants()
            ->validProviders()
            ->notMuted()
            ->when($withoutProvider, function (Builder $query) {
                /** @var Builder|Participant $query */
                return $query->notProvider($this->messenger->getProvider());
            })
            ->get();
    }

    /**
     * @param  Thread  $thread
     * @return Collection
     */
    public function getThreadParticipantsIndex(Thread $thread): Collection
    {
        return $thread->participants()
            ->with('owner')
            ->oldest()
            ->limit($this->messenger->getParticipantsIndexCount())
            ->get();
    }

    /**
     * @param  Thread  $thread
     * @param  Participant  $participant
     * @return Collection
     */
    public function getThreadParticipantsPage(Thread $thread, Participant $participant): Collection
    {
        return $thread->participants()
            ->with('owner')
            ->oldest()
            ->where('created_at', '>=', Helpers::precisionTime($participant->created_at))
            ->where('id', '!=', $participant->id)
            ->limit($this->messenger->getParticipantsPageCount())
            ->get();
    }
}
