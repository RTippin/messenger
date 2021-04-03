<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessageReactionTest extends FeatureTestCase
{
    private MessengerProvider $tippin;
    private Thread $group;
    private Message $message;
    private MessageReaction $reaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->group = $this->createGroupThread($this->tippin);
        $this->message = $this->createMessage($this->group, $this->tippin);
        $this->reaction = MessageReaction::factory()
            ->for($this->message)
            ->for($this->tippin, 'owner')
            ->create([
                'reaction' => ':joy:',
            ]);
    }

    /** @test */
    public function it_exists()
    {
        $this->assertDatabaseCount('message_reactions', 1);
        $this->assertDatabaseHas('message_reactions', [
            'id' => $this->reaction->id,
        ]);
        $this->assertInstanceOf(MessageReaction::class, $this->reaction);
        $this->assertSame(1, MessageReaction::reaction(':joy:')->count());
    }

    /** @test */
    public function it_cast_attributes()
    {
        $this->assertInstanceOf(Carbon::class, $this->message->created_at);
    }

    /** @test */
    public function it_has_relations()
    {
        $this->assertInstanceOf(Message::class, $this->reaction->message);
        $this->assertSame($this->tippin->getKey(), $this->reaction->owner->getKey());
        $this->assertSame($this->message->id, $this->reaction->message->id);
    }
}
