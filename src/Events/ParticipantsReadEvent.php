<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Participant;

class ParticipantsReadEvent
{
    use SerializesModels;

    /**
     * @var Participant
     */
    private Participant $participant;

    /**
     * Create a new event instance.
     *
     * @param Participant $participant
     */
    public function __construct(Participant $participant)
    {
        $this->participant = $participant;
    }
}
