<?php

namespace RTippin\Messenger\Tests\Actions;

use RTippin\Messenger\Actions\Threads\StoreManyParticipants;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreManyParticipantsTest extends FeatureTestCase
{
    private Thread $group;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->tippin);

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function store_many_participants()
    {
        $this->createFriends($this->tippin, $this->doe);

        app(StoreManyParticipants::class)->withoutDispatches()->execute(
            $this->group,
            [
                [
                    'id' => $this->doe->getKey(),
                    'alias' => 'user',
                ],
            ],
        );

        $this->assertDatabaseCount('participants', 2);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => get_class($this->doe),
            'admin' => false,
        ]);
    }
}
