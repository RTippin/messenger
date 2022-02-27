<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Participant;

class ParticipantMutedEvent
{
    use SerializesModels;

    /**
     * @param  Participant  $participant
     */
    public function __construct(
        public Participant $participant
    ){}
}
