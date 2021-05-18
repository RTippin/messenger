<?php

namespace RTippin\Messenger\Tests\JsonResources;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Resources\FriendResource;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Tests\FeatureTestCase;

class FriendResourceTest extends FeatureTestCase
{
    /** @test */
    public function it_transforms_friend()
    {
        Messenger::setProvider($this->tippin);
        $owner = Friend::factory()->providers($this->tippin, $this->doe)->create();
        Friend::factory()->providers($this->doe, $this->tippin)->create();

        $resource = (new FriendResource($owner))->resolve();
        $owner = $owner->toArray();

        $this->assertSame($owner['id'], $resource['id']);
        $this->assertSame('FRIEND', $resource['type_verbose']);
        $this->assertEquals($this->tippin->getKey(), $resource['owner_id']);
        $this->assertSame($this->tippin->getMorphClass(), $resource['owner_type']);
        $this->assertEquals($this->doe->getKey(), $resource['party_id']);
        $this->assertSame($this->doe->getMorphClass(), $resource['party_type']);
        $this->assertSame($owner['created_at'], $resource['created_at']);
        $this->assertSame($owner['updated_at'], $resource['updated_at']);
        $this->assertIsArray($resource['party']);
    }
}
