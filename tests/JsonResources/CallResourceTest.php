<?php

namespace RTippin\Messenger\Tests\JsonResources;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Resources\CallResource;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallResourceTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_transforms_call()
    {
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->create();

        $resource = (new CallResource($call, $thread))->resolve();

        $this->assertSame($call->id, $resource['id']);
        $this->assertSame(1, $resource['type']);
        $this->assertSame('VIDEO', $resource['type_verbose']);
        $this->assertSame($thread->id, $resource['thread_id']);
        $this->assertSame($call->created_at->toDayDateTimeString(), $resource['created_at']->toDayDateTimeString());
        $this->assertSame($call->updated_at->toDayDateTimeString(), $resource['updated_at']->toDayDateTimeString());
        $this->assertEquals($this->tippin->getKey(), $resource['owner_id']);
        $this->assertSame($this->tippin->getMorphClass(), $resource['owner_type']);
        $this->assertIsArray($resource['owner']);
        $this->assertIsArray($resource['meta']);
        $this->assertSame($thread->id, $resource['meta']['thread_id']);
        $this->assertSame(2, $resource['meta']['thread_type']);
        $this->assertSame('GROUP', $resource['meta']['thread_type_verbose']);
        $this->assertSame('Test', $resource['meta']['thread_name']);
        $this->assertIsArray($resource['meta']['thread_avatar']);
    }

    /** @test */
    public function it_transforms_ended_call()
    {
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->ended()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->left()->create();

        $resource = (new CallResource($call, $thread))->resolve();

        $this->assertFalse($resource['active']);
        $this->assertArrayNotHasKey('options', $resource);
        $this->assertArrayNotHasKey('participants', $resource);
    }

    /** @test */
    public function it_transforms_active_call()
    {
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->create();

        $resource = (new CallResource($call, $thread))->resolve();

        $this->assertTrue($resource['active']);
        $this->assertIsArray($resource['options']);
        $this->assertTrue($resource['options']['admin']);
        $this->assertTrue($resource['options']['setup_complete']);
        $this->assertTrue($resource['options']['in_call']);
        $this->assertFalse($resource['options']['left_call']);
        $this->assertTrue($resource['options']['joined']);
        $this->assertFalse($resource['options']['kicked']);
        $this->assertSame(123456789, $resource['options']['room_id']);
        $this->assertSame('PIN', $resource['options']['room_pin']);
        $this->assertSame('PAYLOAD', $resource['options']['payload']);
        $this->assertArrayNotHasKey('participants', $resource);
    }

    /** @test */
    public function it_doesnt_include_room_details_when_active_but_not_setup()
    {
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->create();

        $resource = (new CallResource($call, $thread))->resolve();

        $this->assertFalse($resource['options']['setup_complete']);
        $this->assertArrayNotHasKey('room_id', $resource['options']);
        $this->assertArrayNotHasKey('room_pin', $resource['options']);
        $this->assertArrayNotHasKey('payload', $resource['options']);
    }

    /** @test */
    public function it_doesnt_include_room_details_when_active_but_provider_not_joined()
    {
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();

        $resource = (new CallResource($call, $thread))->resolve();

        $this->assertTrue($resource['options']['setup_complete']);
        $this->assertArrayNotHasKey('room_id', $resource['options']);
        $this->assertArrayNotHasKey('room_pin', $resource['options']);
        $this->assertArrayNotHasKey('payload', $resource['options']);
    }

    /** @test */
    public function it_doesnt_include_room_details_when_active_but_provider_kicked()
    {
        $thread = Thread::factory()->group()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->left()->kicked()->create();

        $resource = (new CallResource($call, $thread))->resolve();

        $this->assertTrue($resource['options']['setup_complete']);
        $this->assertArrayNotHasKey('room_id', $resource['options']);
        $this->assertArrayNotHasKey('room_pin', $resource['options']);
        $this->assertArrayNotHasKey('payload', $resource['options']);
    }

    /** @test */
    public function it_can_add_participant_collection_when_ended()
    {
        $thread = Thread::factory()->group()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->ended()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->left()->create();

        $resource = (new CallResource($call, $thread, true))->resolve();

        $this->assertArrayHasKey('participants', $resource);
        $this->assertIsArray($resource['participants']);
    }

    /** @test */
    public function it_doesnt_add_participant_collection_when_active()
    {
        $thread = Thread::factory()->group()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->create();

        $resource = (new CallResource($call, $thread, true))->resolve();

        $this->assertArrayNotHasKey('participants', $resource);
    }
}
