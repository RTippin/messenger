<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Tests\FeatureTestCase;

class PendingFriendTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $pending = PendingFriend::factory()->providers($this->tippin, $this->doe)->create();

        $this->assertDatabaseCount('pending_friends', 1);
        $this->assertDatabaseHas('pending_friends', [
            'id' => $pending->id,
        ]);
        $this->assertInstanceOf(PendingFriend::class, $pending);
    }

    /** @test */
    public function it_cast_attributes()
    {
        PendingFriend::factory()->providers($this->tippin, $this->doe)->create();
        $pending = PendingFriend::first();

        $this->assertInstanceOf(Carbon::class, $pending->created_at);
        $this->assertInstanceOf(Carbon::class, $pending->updated_at);
    }

    /** @test */
    public function it_has_relations()
    {
        $pending = PendingFriend::factory()->providers($this->tippin, $this->doe)->create();

        $this->assertSame($this->tippin->getKey(), $pending->sender->getKey());
        $this->assertSame($this->doe->getKey(), $pending->recipient->getKey());
        $this->assertInstanceOf(MessengerProvider::class, $pending->sender);
        $this->assertInstanceOf(MessengerProvider::class, $pending->recipient);
    }

    /** @test */
    public function sender_and_recipient_return_ghost_if_not_found()
    {
        $pending = PendingFriend::factory()->create([
            'sender_id' => 404,
            'sender_type' => $this->tippin->getMorphClass(),
            'recipient_id' => 404,
            'recipient_type' => $this->doe->getMorphClass(),
        ]);

        $this->assertInstanceOf(GhostUser::class, $pending->sender);
        $this->assertInstanceOf(GhostUser::class, $pending->recipient);
    }

    /** @test */
    public function its_recipient_is_the_current_provider()
    {
        Messenger::setProvider($this->tippin);
        $pending = PendingFriend::factory()->providers($this->doe, $this->tippin)->create();

        $this->assertTrue($pending->isRecipientCurrentProvider());
    }

    /** @test */
    public function its_recipient_is_not_the_current_provider()
    {
        Messenger::setProvider($this->doe);
        $pending = PendingFriend::factory()->providers($this->doe, $this->tippin)->create();

        $this->assertFalse($pending->isRecipientCurrentProvider());
    }
}
