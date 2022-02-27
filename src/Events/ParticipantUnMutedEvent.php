<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Participant;

class ParticipantUnMutedEvent
{
    use SerializesModels;

    /**
     * @param  Participant  $participant
     */
    public function __construct(
        public Participant $participant
    ){}
}
