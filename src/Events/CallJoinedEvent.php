<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;

class CallJoinedEvent
{
    use SerializesModels;

    /**
     * @var Call
     */
    public Call $call;

    /**
     * @var CallParticipant
     */
    public CallParticipant $participant;

    /**
     * Create a new event instance.
     *
     * @param  Call  $call
     * @param  CallParticipant  $participant
     */
    public function __construct(Call $call, CallParticipant $participant)
    {
        $this->call = $call;
        $this->participant = $participant;
    }
}
