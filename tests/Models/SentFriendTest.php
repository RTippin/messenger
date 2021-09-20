<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\FeatureTestCase;

class SentFriendTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $sent = SentFriend::factory()->providers($this->tippin, $this->doe)->create();

        $this->assertDatabaseCount('pending_friends', 1);
        $this->assertDatabaseHas('pending_friends', [
            'id' => $sent->id,
        ]);
        $this->assertInstanceOf(SentFriend::class, $sent);
    }

    /** @test */
    public function it_cast_attributes()
    {
        SentFriend::factory()->providers($this->tippin, $this->doe)->create();
        $sent = SentFriend::first();

        $this->assertInstanceOf(Carbon::class, $sent->created_at);
        $this->assertInstanceOf(Carbon::class, $sent->updated_at);
    }

    /** @test */
    public function it_has_relations()
    {
        $sent = SentFriend::factory()->providers($this->tippin, $this->doe)->create();

        $this->assertSame($this->tippin->getKey(), $sent->sender->getKey());
        $this->assertSame($this->doe->getKey(), $sent->recipient->getKey());
        $this->assertInstanceOf(MessengerProvider::class, $sent->sender);
        $this->assertInstanceOf(MessengerProvider::class, $sent->recipient);
    }

    /** @test */
    public function sender_and_recipient_return_ghost_if_not_found()
    {
        $sent = SentFriend::factory()->create([
            'sender_id' => 404,
            'sender_type' => $this->tippin->getMorphClass(),
            'recipient_id' => 404,
            'recipient_type' => $this->doe->getMorphClass(),
        ]);

        $this->assertInstanceOf(GhostUser::class, $sent->sender);
        $this->assertInstanceOf(GhostUser::class, $sent->recipient);
    }

    /** @test */
    public function its_sender_is_the_current_provider()
    {
        Messenger::setProvider($this->tippin);
        $sent = SentFriend::factory()->providers($this->tippin, $this->doe)->create();

        $this->assertTrue($sent->isSenderCurrentProvider());
    }

    /** @test */
    public function its_sender_is_not_the_current_provider()
    {
        Messenger::setProvider($this->doe);
        $sent = SentFriend::factory()->providers($this->tippin, $this->doe)->create();

        $this->assertFalse($sent->isSenderCurrentProvider());
    }
}
