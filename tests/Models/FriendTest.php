<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Tests\FeatureTestCase;

class FriendTest extends FeatureTestCase
{
    private Friend $friend;

    protected function setUp(): void
    {
        parent::setUp();

        $this->friend = Friend::factory()->providers($this->tippin, $this->doe)->create();
    }

    /** @test */
    public function it_exists()
    {
        $this->assertDatabaseCount('friends', 1);
        $this->assertDatabaseHas('friends', [
            'id' => $this->friend->id,
        ]);
        $this->assertInstanceOf(Friend::class, $this->friend);
    }

    /** @test */
    public function it_cast_attributes()
    {
        $this->assertInstanceOf(Carbon::class, $this->friend->created_at);
        $this->assertInstanceOf(Carbon::class, $this->friend->updated_at);
    }

    /** @test */
    public function it_has_relations()
    {
        $this->assertSame($this->tippin->getKey(), $this->friend->owner->getKey());
        $this->assertSame($this->doe->getKey(), $this->friend->party->getKey());
        $this->assertInstanceOf(MessengerProvider::class, $this->friend->owner);
        $this->assertInstanceOf(MessengerProvider::class, $this->friend->party);
    }

    /** @test */
    public function relations_return_ghost_if_not_found()
    {
        $this->friend->update([
            'owner_id' => 404,
            'party_id' => 404,
        ]);

        $this->assertInstanceOf(GhostUser::class, $this->friend->owner);
        $this->assertInstanceOf(GhostUser::class, $this->friend->party);
    }
}
