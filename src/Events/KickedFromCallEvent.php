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
     * @param  MessengerProvider  $provider
     * @param  Call  $call
     * @param  CallParticipant  $participant
     */
    public function __construct(
        public MessengerProvider $provider,
        public Call $call,
        public CallParticipant $participant
    ){}
}
