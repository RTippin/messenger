<?php

namespace RTippin\Messenger\Tests\JsonResources;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Resources\SentFriendResource;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\FeatureTestCase;

class SentFriendResourceTest extends FeatureTestCase
{
    /** @test */
    public function it_transforms_sent_friend()
    {
        Messenger::setProvider($this->tippin);
        $sender = SentFriend::factory()->providers($this->tippin, $this->doe)->create();

        $resource = (new SentFriendResource($sender))->resolve();
        $sender = $sender->toArray();

        $this->assertSame($sender['id'], $resource['id']);
        $this->assertSame('SENT_FRIEND_REQUEST', $resource['type_verbose']);
        $this->assertEquals($this->doe->getKey(), $resource['recipient_id']);
        $this->assertSame($this->doe->getMorphClass(), $resource['recipient_type']);
        $this->assertEquals($this->tippin->getKey(), $resource['sender_id']);
        $this->assertSame($this->tippin->getMorphClass(), $resource['sender_type']);
        $this->assertSame($sender['created_at'], $resource['created_at']);
        $this->assertSame($sender['updated_at'], $resource['updated_at']);
        $this->assertIsArray($resource['recipient']);
    }
}
