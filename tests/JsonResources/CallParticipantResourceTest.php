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
    private Thread $group;
    private Call $call;
    private CallParticipant $callParticipant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin);
        $this->call = $this->createCall($this->group, $this->tippin);
        $this->callParticipant = $this->call->participants()->first();
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_transforms_call_participant()
    {
        $resource = (new CallParticipantResource($this->callParticipant))->resolve();

        $this->assertSame($this->callParticipant->id, $resource['id']);
        $this->assertSame($this->call->id, $resource['call_id']);
        $this->assertEquals($this->tippin->getKey(), $resource['owner_id']);
        $this->assertSame(get_class($this->tippin), $resource['owner_type']);
        $this->assertSame($this->callParticipant->created_at->toDayDateTimeString(), $resource['created_at']->toDayDateTimeString());
        $this->assertSame($this->callParticipant->updated_at->toDayDateTimeString(), $resource['updated_at']->toDayDateTimeString());
        $this->assertNull($resource['left_call']);
        $this->assertIsArray($resource['owner']);
    }
}
