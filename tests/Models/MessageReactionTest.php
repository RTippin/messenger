<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessageReactionTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $reaction = MessageReaction::factory()->for(
            Message::factory()->for(
                Thread::factory()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->reaction(':test:')->create();

        $this->assertDatabaseCount('message_reactions', 1);
        $this->assertDatabaseHas('message_reactions', [
            'id' => $reaction->id,
        ]);
        $this->assertInstanceOf(MessageReaction::class, $reaction);
        $this->assertSame(1, MessageReaction::whereReaction(':test:')->count());
        $this->assertSame(0, MessageReaction::notReaction(':test:')->count());
    }

    /** @test */
    public function it_cast_attributes()
    {
        $reaction = MessageReaction::factory()->for(
            Message::factory()->for(
                Thread::factory()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertInstanceOf(Carbon::class, $reaction->created_at);
    }

    /** @test */
    public function it_has_relations()
    {
        $message = Message::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->create();
        $reaction = MessageReaction::factory()->for($message)->owner($this->tippin)->create();

        $this->assertInstanceOf(Message::class, $reaction->message);
        $this->assertSame($this->tippin->getKey(), $reaction->owner->getKey());
        $this->assertSame($message->id, $reaction->message->id);
    }

    /** @test */
    public function owner_returns_ghost_if_not_found()
    {
        $reaction = MessageReaction::factory()->for(
            Message::factory()->for(
                Thread::factory()->create()
            )->owner($this->tippin)->create()
        )->create([
            'owner_id' => 404,
            'owner_type' => $this->tippin->getMorphClass(),
        ]);

        $this->assertInstanceOf(GhostUser::class, $reaction->owner);
    }

    /** @test */
    public function it_is_owned_by_current_provider()
    {
        Messenger::setProvider($this->tippin);
        $reaction = MessageReaction::factory()->for(
            Message::factory()->for(
                Thread::factory()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertTrue($reaction->isOwnedByCurrentProvider());
    }

    /** @test */
    public function it_is_not_owned_by_current_provider()
    {
        Messenger::setProvider($this->doe);
        $reaction = MessageReaction::factory()->for(
            Message::factory()->for(
                Thread::factory()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertFalse($reaction->isOwnedByCurrentProvider());
    }

    /** @test */
    public function it_has_private_owner_channel()
    {
        $reaction = MessageReaction::factory()->for(
            Message::factory()->for(
                Thread::factory()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertSame('user.'.$this->tippin->getKey(), $reaction->getOwnerPrivateChannel());
    }
}
