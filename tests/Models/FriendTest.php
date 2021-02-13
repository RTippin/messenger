<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Tests\FeatureTestCase;

class FriendTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    private Friend $friend;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->friend = Friend::create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'party_id' => $this->doe->getKey(),
            'party_type' => get_class($this->doe),
        ]);
    }

    /** @test */
    public function friend_exists()
    {
        $this->assertDatabaseCount('friends', 1);
        $this->assertDatabaseHas('friends', [
            'id' => $this->friend->id,
        ]);
        $this->assertInstanceOf(Friend::class, $this->friend);
    }

    /** @test */
    public function friend_attributes_casted()
    {
        $this->assertInstanceOf(Carbon::class, $this->friend->created_at);
        $this->assertInstanceOf(Carbon::class, $this->friend->updated_at);
    }

    /** @test */
    public function friend_has_relations()
    {
        $this->assertSame($this->friend->owner_id, $this->friend->owner->getKey());
        $this->assertSame($this->friend->party_id, $this->friend->party->getKey());
        $this->assertInstanceOf(MessengerProvider::class, $this->friend->owner);
        $this->assertInstanceOf(MessengerProvider::class, $this->friend->party);
    }

    /** @test */
    public function friend_relations_return_ghost_when_not_found()
    {
        $this->friend->update([
            'owner_id' => 404,
            'party_id' => 404,
        ]);

        $this->assertInstanceOf(GhostUser::class, $this->friend->owner);
        $this->assertInstanceOf(GhostUser::class, $this->friend->party);
    }
}
