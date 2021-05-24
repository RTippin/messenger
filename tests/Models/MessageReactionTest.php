<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
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
        $this->assertSame(1, MessageReaction::reaction(':test:')->count());
    }

    /** @test */
    public function it_cast_attributes()
    {
        MessageReaction::factory()->for(
            Message::factory()->for(
                Thread::factory()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        $reaction = MessageReaction::first();

        $this->assertInstanceOf(Carbon::class, $reaction->created_at);
    }

    /** @test */
    public function it_has_relations()
    {
        $message = Message::factory()->for(Thread::factory()->create())->owner($this->tippin)->create();
        $reaction = MessageReaction::factory()->for($message)->owner($this->tippin)->create();

        $this->assertInstanceOf(Message::class, $reaction->message);
        $this->assertSame($this->tippin->getKey(), $reaction->owner->getKey());
        $this->assertSame($message->id, $reaction->message->id);
    }
}
