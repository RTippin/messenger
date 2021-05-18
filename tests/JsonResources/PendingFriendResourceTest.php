<?php

namespace RTippin\Messenger\Tests\JsonResources;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Resources\PendingFriendResource;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Tests\FeatureTestCase;

class PendingFriendResourceTest extends FeatureTestCase
{
    /** @test */
    public function it_transforms_pending_friend()
    {
        Messenger::setProvider($this->tippin);
        $recipient = PendingFriend::factory()->providers($this->doe, $this->tippin)->create();

        $resource = (new PendingFriendResource($recipient))->resolve();
        $recipient = $recipient->toArray();

        $this->assertSame($recipient['id'], $resource['id']);
        $this->assertSame('PENDING_FRIEND_REQUEST', $resource['type_verbose']);
        $this->assertEquals($this->tippin->getKey(), $resource['recipient_id']);
        $this->assertSame($this->tippin->getMorphClass(), $resource['recipient_type']);
        $this->assertEquals($this->doe->getKey(), $resource['sender_id']);
        $this->assertSame($this->doe->getMorphClass(), $resource['sender_type']);
        $this->assertSame($recipient['created_at'], $resource['created_at']);
        $this->assertSame($recipient['updated_at'], $resource['updated_at']);
        $this->assertIsArray($resource['sender']);
    }
}
