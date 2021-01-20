<?php

namespace RTippin\Messenger\Tests\Actions;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallBrokerSetupTest extends FeatureTestCase
{
    private Thread $group;

    private Call $call;

    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->group = $this->createGroupThread($this->tippin);

        $this->call = $this->group->calls()->create([
            'type' => 1,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'call_ended' => null,
            'setup_complete' => false,
            'room_id' => null,
            'room_pin' => null,
            'room_secret' => null,
        ]);
    }
}
