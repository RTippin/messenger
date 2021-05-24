<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Tests\FeatureTestCase;

class FriendTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $friend = Friend::factory()->providers($this->tippin, $this->doe)->create();

        $this->assertDatabaseCount('friends', 1);
        $this->assertDatabaseHas('friends', [
            'id' => $friend->id,
        ]);
        $this->assertInstanceOf(Friend::class, $friend);
    }

    /** @test */
    public function it_cast_attributes()
    {
        Friend::factory()->providers($this->tippin, $this->doe)->create();
        $friend = Friend::first();

        $this->assertInstanceOf(Carbon::class, $friend->created_at);
        $this->assertInstanceOf(Carbon::class, $friend->updated_at);
    }

    /** @test */
    public function it_has_relations()
    {
        $friend = Friend::factory()->providers($this->tippin, $this->doe)->create();

        $this->assertSame($this->tippin->getKey(), $friend->owner->getKey());
        $this->assertSame($this->doe->getKey(), $friend->party->getKey());
        $this->assertInstanceOf(MessengerProvider::class, $friend->owner);
        $this->assertInstanceOf(MessengerProvider::class, $friend->party);
    }

    /** @test */
    public function relations_return_ghost_if_not_found()
    {
        $friend = Friend::factory()->create([
            'owner_id' => 404,
            'owner_type' => $this->tippin->getMorphClass(),
            'party_id' => 404,
            'party_type' => $this->doe->getMorphClass(),
        ]);

        $this->assertInstanceOf(GhostUser::class, $friend->owner);
        $this->assertInstanceOf(GhostUser::class, $friend->party);
    }
}
