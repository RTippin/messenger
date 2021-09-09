<?php

namespace RTippin\Messenger\Actions\Threads;

use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Participant;

abstract class ThreadParticipantAction extends BaseMessengerAction
{
    /**
     * Store a fresh new participant.
     *
     * @param  MessengerProvider  $provider
     * @param  array  $attributes
     * @return $this
     */
    protected function storeParticipant(MessengerProvider $provider, array $attributes = []): self
    {
        $this->setParticipant(
            $this->getThread()
                ->participants()
                ->create(array_merge($attributes, [
                    'owner_id' => $provider->getKey(),
                    'owner_type' => $provider->getMorphClass(),
                ]))
                ->setRelation('owner', $provider)
        );

        return $this;
    }

    /**
     * Store or restore a group participant.
     *
     * @param  MessengerProvider  $provider
     * @return $this
     */
    protected function storeOrRestoreParticipant(MessengerProvider $provider): self
    {
        /** @var Participant $participant */
        $participant = $this->getThread()
            ->participants()
            ->withTrashed()
            ->firstOrCreate([
                'owner_id' => $provider->getKey(),
                'owner_type' => $provider->getMorphClass(),
            ])
            ->setRelation('owner', $provider);

        if ($participant->trashed()) {
            $participant->update(Participant::DefaultPermissions);
        }

        $this->setParticipant($participant);

        return $this;
    }

    /**
     * @param  Participant  $participant
     * @param  array  $attributes
     * @return $this
     */
    protected function updateParticipant(Participant $participant, array $attributes): self
    {
        $participant->update($attributes);

        $this->setParticipant($participant);

        return $this;
    }
}
