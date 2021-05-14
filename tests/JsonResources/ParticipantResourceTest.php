<?php

namespace RTippin\Messenger\Tests\JsonResources;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Resources\ParticipantResource;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ParticipantResourceTest extends FeatureTestCase
{
    /** @test */
    public function it_transforms_private_thread_participant_no_messages()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();

        $resource = (new ParticipantResource($participant, $thread))->resolve();

        $this->assertSame($participant->id, $resource['id']);
        $this->assertSame($thread->id, $resource['thread_id']);
        $this->assertFalse($resource['admin']);
        $this->assertFalse($resource['pending']);
        $this->assertFalse($resource['send_knocks']);
        $this->assertTrue($resource['send_messages']);
        $this->assertFalse($resource['add_participants']);
        $this->assertFalse($resource['manage_invites']);
        $this->assertFalse($resource['start_calls']);
        $this->assertEquals($this->tippin->getKey(), $resource['owner_id']);
        $this->assertSame($this->tippin->getMorphClass(), $resource['owner_type']);
        $this->assertIsArray($resource['owner']);
        $this->assertSame($participant->created_at->format('Y-m-d H:i:s.u'), $resource['created_at']->format('Y-m-d H:i:s.u'));
        $this->assertSame($participant->updated_at->format('Y-m-d H:i:s.u'), $resource['updated_at']->format('Y-m-d H:i:s.u'));
        $this->assertNull($resource['last_read']['time']);
        $this->assertNull($resource['last_read']['message_id']);
    }
}
