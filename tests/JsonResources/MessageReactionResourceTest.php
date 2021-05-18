<?php

namespace RTippin\Messenger\Tests\JsonResources;

use RTippin\Messenger\Http\Resources\MessageReactionResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessageReactionResourceTest extends FeatureTestCase
{
    /** @test */
    public function it_transforms_reaction()
    {
        $message = Message::factory()->for(Thread::factory()->create())->owner($this->tippin)->create();
        $reaction = MessageReaction::factory()->for($message)->owner($this->tippin)->create();

        $resource = (new MessageReactionResource($reaction))->resolve();

        $this->assertSame($reaction->id, $resource['id']);
        $this->assertSame($reaction->reaction, $resource['reaction']);
        $this->assertSame($message->id, $resource['message_id']);
        $this->assertSame($reaction->created_at->format('Y-m-d H:i:s.u'), $resource['created_at']->format('Y-m-d H:i:s.u'));
        $this->assertEquals($this->tippin->getKey(), $resource['owner_id']);
        $this->assertSame($this->tippin->getMorphClass(), $resource['owner_type']);
        $this->assertIsArray($resource['owner']);
        $this->assertArrayNotHasKey('message', $resource);
    }

    /** @test */
    public function it_transforms_reaction_and_adds_message()
    {
        $message = Message::factory()->for(Thread::factory()->create())->owner($this->tippin)->create();
        $reaction = MessageReaction::factory()->for($message)->owner($this->tippin)->create();

        $resource = (new MessageReactionResource($reaction, $message))->resolve();

        $this->assertIsArray($resource['message']);
    }
}
