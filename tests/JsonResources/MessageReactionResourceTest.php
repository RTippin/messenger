<?php

namespace RTippin\Messenger\Tests\JsonResources;

use RTippin\Messenger\Http\Resources\MessageReactionResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessageReactionResourceTest extends FeatureTestCase
{
    private Message $message;
    private MessageReaction $reaction;

    protected function setUp(): void
    {
        parent::setUp();

        $group = Thread::factory()->group()->create();
        $this->message = Message::factory()->for($group)->owner($this->tippin)->create();
        $this->reaction = MessageReaction::factory()->for($this->message)->owner($this->tippin)->create();
    }

    /** @test */
    public function it_transforms_reaction()
    {
        $resource = (new MessageReactionResource($this->reaction))->resolve();

        $this->assertSame($this->reaction->id, $resource['id']);
        $this->assertSame($this->reaction->reaction, $resource['reaction']);
        $this->assertSame($this->message->id, $resource['message_id']);
        $this->assertSame($this->reaction->created_at->format('Y-m-d H:i:s.u'), $resource['created_at']->format('Y-m-d H:i:s.u'));
        $this->assertEquals($this->tippin->getKey(), $resource['owner_id']);
        $this->assertSame($this->tippin->getMorphClass(), $resource['owner_type']);
        $this->assertIsArray($resource['owner']);
        $this->assertArrayNotHasKey('message', $resource);
    }

    /** @test */
    public function it_transforms_reaction_and_adds_message()
    {
        $resource = (new MessageReactionResource($this->reaction, $this->message))->resolve();

        $this->assertIsArray($resource['message']);
    }
}
