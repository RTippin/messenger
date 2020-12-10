<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Contracts\Cache\Repository;
use RTippin\Messenger\Actions\Base\BaseMessengerAction;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\CallParticipant;

abstract class CallParticipantAction extends BaseMessengerAction
{
    /**
     * @var Repository
     */
    protected Repository $cacheDriver;

    /**
     * CallParticipantAction constructor.
     *
     * @param Repository $cacheDriver
     */
    public function __construct(Repository $cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;
    }

    /**
     * Store a fresh new participant.
     *
     * @param MessengerProvider $provider
     * @return $this
     */
    protected function storeParticipant(MessengerProvider $provider): self
    {
        $this->setCallParticipant(
            $this->getCall()
            ->participants()
            ->create([
                'owner_id' => $provider->getKey(),
                'owner_type' => get_class($provider),
            ])
            ->setRelation('owner', $provider)
        );

        return $this;
    }

    /**
     * @param CallParticipant $participant
     * @param array $attributes
     * @return $this
     */
    protected function updateParticipant(CallParticipant $participant, array $attributes): self
    {
        $participant->update($attributes);

        $this->setCallParticipant($participant);

        return $this;
    }

    /**
     * Put the participant in cache so that we may tell if a participant
     * left without a proper post to the backend (left_call null).
     *
     * @param CallParticipant $participant
     * @return $this
     */
    protected function setParticipantInCallCache(CallParticipant $participant): self
    {
        $this->cacheDriver->put("call:{$this->getCall()->id}:{$participant->id}", true, 60);

        return $this;
    }

    /**
     * Remove the participant from cache.
     *
     * @param CallParticipant $participant
     * @return $this
     */
    protected function removeParticipantInCallCache(CallParticipant $participant): self
    {
        $this->cacheDriver->forget("call:{$this->getCall()->id}:{$participant->id}");

        return $this;
    }
}
