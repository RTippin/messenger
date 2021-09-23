<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $call = Call::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->setup()->create();

        $this->assertDatabaseCount('calls', 1);
        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
        ]);
        $this->assertInstanceOf(Call::class, $call);
        $this->assertSame(1, Call::videoCall()->count());
        $this->assertSame(1, Call::active()->count());
    }

    /** @test */
    public function it_cast_attributes()
    {
        $call = Call::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->ended()->create();

        $this->assertInstanceOf(Carbon::class, $call->created_at);
        $this->assertInstanceOf(Carbon::class, $call->updated_at);
        $this->assertInstanceOf(Carbon::class, $call->call_ended);
        $this->assertSame(1, $call->type);
        $this->assertSame(true, $call->setup_complete);
        $this->assertSame(true, $call->teardown_complete);
        $this->assertSame(123456789, $call->room_id);
        $this->assertSame('PIN', $call->room_pin);
        $this->assertSame('SECRET', $call->room_secret);
        $this->assertSame('PAYLOAD', $call->payload);
    }

    /** @test */
    public function it_has_relations()
    {
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        CallParticipant::factory()->for($call)->owner($this->doe)->create();

        $this->assertSame($thread->id, $call->thread->id);
        $this->assertSame($this->tippin->getKey(), $call->owner->getKey());
        $this->assertCount(2, $call->participants);
        $this->assertInstanceOf(Thread::class, $call->thread);
        $this->assertInstanceOf(MessengerProvider::class, $call->owner);
        $this->assertInstanceOf(Collection::class, $call->participants);
    }

    /** @test */
    public function it_has_presence_channel()
    {
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();

        $this->assertSame("call.$call->id.thread.$thread->id", $call->getPresenceChannel());
    }

    /** @test */
    public function owner_returns_ghost_if_not_found()
    {
        $call = Call::factory()->for(
            Thread::factory()->create()
        )->create([
            'owner_id' => 404,
            'owner_type' => $this->tippin->getMorphClass(),
        ]);

        $this->assertInstanceOf(GhostUser::class, $call->owner);
    }

    /** @test */
    public function it_is_owned_by_current_provider()
    {
        Messenger::setProvider($this->tippin);
        $call = Call::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->create();

        $this->assertTrue($call->isOwnedByCurrentProvider());
    }

    /** @test */
    public function it_is_not_owned_by_current_provider()
    {
        Messenger::setProvider($this->doe);
        $call = Call::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->create();

        $this->assertFalse($call->isOwnedByCurrentProvider());
    }

    /** @test */
    public function it_has_private_owner_channel()
    {
        $call = Call::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->create();

        $this->assertSame('user.'.$this->tippin->getKey(), $call->getOwnerPrivateChannel());
    }

    /** @test */
    public function call_type_verbose()
    {
        $call = Call::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->create();

        $this->assertSame('VIDEO', $call->getTypeVerbose());
    }

    /** @test */
    public function active_boolean()
    {
        $thread = Thread::factory()->create();
        $call1 = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $call2 = Call::factory()->for($thread)->owner($this->tippin)->ended()->create();

        $this->assertTrue($call1->isActive());
        $this->assertFalse($call2->isActive());
    }

    /** @test */
    public function is_setup_boolean()
    {
        $thread = Thread::factory()->create();
        $call1 = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $call2 = Call::factory()->for($thread)->owner($this->tippin)->create();

        $this->assertTrue($call1->isSetup());
        $this->assertFalse($call2->isSetup());
    }

    /** @test */
    public function is_torn_down_boolean()
    {
        $thread = Thread::factory()->create();
        $call1 = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $call2 = Call::factory()->for($thread)->owner($this->tippin)->ended()->create();

        $this->assertFalse($call1->isTornDown());
        $this->assertTrue($call2->isTornDown());
    }

    /** @test */
    public function has_ended_boolean()
    {
        $thread = Thread::factory()->create();
        $call1 = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $call2 = Call::factory()->for($thread)->owner($this->tippin)->ended()->create();

        $this->assertFalse($call1->hasEnded());
        $this->assertTrue($call2->hasEnded());
        $this->assertSame(1, Call::active()->count());
    }

    /** @test */
    public function is_video_call_boolean()
    {
        $thread = Thread::factory()->create();
        $call1 = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $call2 = Call::factory()->for($thread)->owner($this->tippin)->create(['type' => 2]);

        $this->assertTrue($call1->isVideoCall());
        $this->assertFalse($call2->isVideoCall());
    }

    /** @test */
    public function group_call_boolean()
    {
        $thread1 = Thread::factory()->group()->create();
        $thread2 = Thread::factory()->create();
        $call1 = Call::factory()->for($thread1)->owner($this->tippin)->create();
        $call2 = Call::factory()->for($thread2)->owner($this->tippin)->create();

        $this->assertTrue($call1->isGroupCall());
        $this->assertTrue($call1->isGroupCall($thread1));
        $this->assertFalse($call2->isGroupCall());
        $this->assertFalse($call2->isGroupCall($thread2));
    }

    /** @test */
    public function it_has_thread_name()
    {
        Messenger::setProvider($this->tippin);
        $thread1 = $this->createPrivateThread($this->tippin, $this->doe);
        $thread2 = Thread::factory()->group()->create(['subject' => 'Test']);
        $call1 = Call::factory()->for($thread1)->owner($this->tippin)->create();
        $call2 = Call::factory()->for($thread2)->owner($this->tippin)->create();

        $this->assertSame('John Doe', $call1->name());
        $this->assertSame('John Doe', $call1->name($thread1));
        $this->assertSame('Test', $call2->name());
        $this->assertSame('Test', $call2->name($thread2));
    }

    /** @test */
    public function it_doesnt_have_current_participant_if_provider_not_set()
    {
        $call = $this->createCall(Thread::factory()->create(), $this->tippin);

        $this->assertNull($call->currentCallParticipant());
    }

    /** @test */
    public function it_has_current_participant()
    {
        Messenger::setProvider($this->tippin);
        $call = $this->createCall(Thread::factory()->create(), $this->tippin);
        $participant = $call->currentCallParticipant();

        $this->assertSame($participant, $call->currentCallParticipant());
        $this->assertInstanceOf(CallParticipant::class, $participant);
        $this->assertEquals($this->tippin->getKey(), $participant->owner_id);
    }

    /** @test */
    public function it_has_no_admin_when_ended()
    {
        Messenger::setProvider($this->tippin);
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->ended()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->left()->create();

        $this->assertFalse($call->isCallAdmin());
        $this->assertFalse($call->isCallAdmin($thread));
    }

    /** @test */
    public function is_admin_if_call_creator()
    {
        Messenger::setProvider($this->tippin);
        $thread = $this->createGroupThread($this->tippin);
        $call = $this->createCall($thread, $this->tippin);

        $this->assertTrue($call->isCallAdmin());
        $this->assertTrue($call->isCallAdmin($thread));
    }

    /** @test */
    public function admin_false_if_not_creator_or_group_thread_admin()
    {
        Messenger::setProvider($this->doe);
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $call = $this->createCall($thread, $this->tippin, $this->doe);

        $this->assertFalse($call->isCallAdmin());
        $this->assertFalse($call->isCallAdmin($thread));
    }

    /** @test */
    public function is_admin_if_group_admin()
    {
        Messenger::setProvider($this->doe);
        $thread = $this->createGroupThread($this->tippin);
        Participant::factory()->for($thread)->owner($this->doe)->admin()->create();
        $call = $this->createCall($thread, $this->tippin, $this->doe);

        $this->assertTrue($call->isCallAdmin());
        $this->assertTrue($call->isCallAdmin($thread));
    }

    /** @test */
    public function has_joined_false_if_not_joined()
    {
        Messenger::setProvider($this->doe);
        $call = Call::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->setup()->create();

        $this->assertFalse($call->hasJoinedCall());
    }

    /** @test */
    public function has_joined_true_if_joined()
    {
        Messenger::setProvider($this->tippin);
        $call = $this->createCall(Thread::factory()->create(), $this->tippin);

        $this->assertTrue($call->hasJoinedCall());
    }

    /** @test */
    public function call_participant_kicked_boolean()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->create();
        $call1 = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call1)->owner($this->tippin)->create();
        $call2 = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call2)->owner($this->tippin)->kicked()->create();

        $this->assertFalse($call1->wasKicked());
        $this->assertTrue($call2->wasKicked());
    }

    /** @test */
    public function not_in_call_when_call_ended()
    {
        Messenger::setProvider($this->tippin);
        $call = Call::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->ended()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->create();

        $this->assertFalse($call->isInCall());
    }

    /** @test */
    public function participant_in_call_boolean()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->create();
        $call1 = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call1)->owner($this->tippin)->create();
        $call2 = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $call3 = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call3)->owner($this->tippin)->left()->create();

        $this->assertTrue($call1->isInCall());
        $this->assertFalse($call1->hasLeftCall());
        $this->assertFalse($call2->isInCall());
        $this->assertFalse($call2->hasLeftCall());
        $this->assertFalse($call3->isInCall());
        $this->assertTrue($call3->hasLeftCall());
    }
}
