<?php

namespace RTippin\Messenger\Tests\JsonResources;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Resources\CallResource;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallResourceTest extends FeatureTestCase
{
    private Thread $group;
    private Call $call;
    private CallParticipant $callParticipant;
    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->group = $this->createGroupThread($this->tippin);
        $this->call = $this->createCall($this->group, $this->tippin);
        $this->callParticipant = $this->call->participants()->first();
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_transforms_active_call()
    {
        $resource = (new CallResource($this->call, $this->group))->resolve();

        dump($resource);

        $this->assertSame($this->call->id, $resource['id']);
        $this->assertTrue($resource['active']);
        $this->assertSame(1, $resource['type']);
        $this->assertSame('VIDEO', $resource['type_verbose']);
        $this->assertSame($this->group->id, $resource['thread_id']);
        $this->assertSame($this->call->created_at->toDayDateTimeString(), $resource['created_at']->toDayDateTimeString());
        $this->assertSame($this->call->updated_at->toDayDateTimeString(), $resource['updated_at']->toDayDateTimeString());
        $this->assertEquals($this->tippin->getKey(), $resource['owner_id']);
        $this->assertSame(get_class($this->tippin), $resource['owner_type']);
        $this->assertIsArray($resource['owner']);
        $this->assertIsArray($resource['meta']);
        $this->assertSame($this->group->id, $resource['meta']['thread_id']);
        $this->assertSame(2, $resource['meta']['thread_type']);
        $this->assertSame('GROUP', $resource['meta']['thread_type_verbose']);
        $this->assertSame('First Test Group', $resource['meta']['thread_name']);
        $this->assertIsArray($resource['meta']['api_thread_avatar']);
        $this->assertIsArray($resource['meta']['thread_avatar']);
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
}
