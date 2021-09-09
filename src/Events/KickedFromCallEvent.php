<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;

class KickedFromCallEvent
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
     * @var MessengerProvider
     */
    public MessengerProvider $provider;

    /**
     * Create a new event instance.
     *
     * @param  MessengerProvider  $provider
     * @param  Call  $call
     * @param  CallParticipant  $participant
     */
    public function __construct(MessengerProvider $provider,
                                Call $call,
                                CallParticipant $participant)
    {
        $this->call = $call;
        $this->participant = $participant;
        $this->provider = $provider;
    }
}
