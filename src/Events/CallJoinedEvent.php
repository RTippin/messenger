<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;

class CallJoinedEvent
{
    use SerializesModels;

    /**
     * @param  Call  $call
     * @param  CallParticipant  $participant
     */
    public function __construct(
        public Call $call,
        public CallParticipant $participant
    ){}
}
