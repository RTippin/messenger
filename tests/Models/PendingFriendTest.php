<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Tests\FeatureTestCase;

class PendingFriendTest extends FeatureTestCase
{
    private PendingFriend $pending;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pending = PendingFriend::factory()->providers($this->tippin, $this->doe)->create();
    }

    /** @test */
    public function it_exists()
    {
        $this->assertDatabaseCount('pending_friends', 1);
        $this->assertDatabaseHas('pending_friends', [
            'id' => $this->pending->id,
        ]);
        $this->assertInstanceOf(PendingFriend::class, $this->pending);
    }

    /** @test */
    public function it_cast_attributes()
    {
        $this->assertInstanceOf(Carbon::class, $this->pending->created_at);
        $this->assertInstanceOf(Carbon::class, $this->pending->updated_at);
    }

    /** @test */
    public function it_has_relations()
    {
        $this->assertSame($this->tippin->getKey(), $this->pending->sender->getKey());
        $this->assertSame($this->doe->getKey(), $this->pending->recipient->getKey());
        $this->assertInstanceOf(MessengerProvider::class, $this->pending->sender);
        $this->assertInstanceOf(MessengerProvider::class, $this->pending->recipient);
    }

    /** @test */
    public function sender_and_recipient_return_ghost_if_not_found()
    {
        $this->pending->update([
            'sender_id' => 404,
            'recipient_id' => 404,
        ]);

        $this->assertInstanceOf(GhostUser::class, $this->pending->sender);
        $this->assertInstanceOf(GhostUser::class, $this->pending->recipient);
    }
}
