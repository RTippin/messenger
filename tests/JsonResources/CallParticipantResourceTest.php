<?php

namespace RTippin\Messenger\Tests\JsonResources;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Resources\CallParticipantResource;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallParticipantResourceTest extends FeatureTestCase
{
    /** @test */
    public function it_transforms_call_participant()
    {
        Messenger::setProvider($this->tippin);
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->tippin)->create();

        $resource = (new CallParticipantResource($participant))->resolve();

        $this->assertSame($participant->id, $resource['id']);
        $this->assertSame($call->id, $resource['call_id']);
        $this->assertEquals($this->tippin->getKey(), $resource['owner_id']);
        $this->assertSame($this->tippin->getMorphClass(), $resource['owner_type']);
        $this->assertSame($participant->created_at->toDayDateTimeString(), $resource['created_at']->toDayDateTimeString());
        $this->assertSame($participant->updated_at->toDayDateTimeString(), $resource['updated_at']->toDayDateTimeString());
        $this->assertNull($resource['left_call']);
        $this->assertIsArray($resource['owner']);
    }
}
