<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\Definitions;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallTest extends FeatureTestCase
{
    private MessengerProvider $tippin;
    private MessengerProvider $doe;
    private Thread $group;
    private Call $call;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
        $this->group = $this->createGroupThread($this->tippin, $this->doe);
        $this->call = $this->createCall($this->group, $this->tippin, $this->doe);
    }

    /** @test */
    public function it_exists()
    {
        $this->assertDatabaseCount('calls', 1);
        $this->assertDatabaseHas('calls', [
            'id' => $this->call->id,
        ]);
        $this->assertInstanceOf(Call::class, $this->call);
        $this->assertSame(1, Call::videoCall()->count());
        $this->assertSame(1, Call::active()->count());
    }

    /** @test */
    public function it_cast_attributes()
    {
        $this->call->update([
            'call_ended' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $this->call->created_at);
        $this->assertInstanceOf(Carbon::class, $this->call->updated_at);
        $this->assertInstanceOf(Carbon::class, $this->call->call_ended);
        $this->assertSame(1, $this->call->type);
        $this->assertSame(true, $this->call->setup_complete);
        $this->assertSame(false, $this->call->teardown_complete);
        $this->assertSame(123456789, $this->call->room_id);
        $this->assertSame('PIN', $this->call->room_pin);
        $this->assertSame('SECRET', $this->call->room_secret);
        $this->assertSame('PAYLOAD', $this->call->payload);
    }

    /** @test */
    public function it_has_relations()
    {
        $this->assertSame($this->group->id, $this->call->thread->id);
        $this->assertSame($this->tippin->getKey(), $this->call->owner->getKey());
        $this->assertCount(2, $this->call->participants);
        $this->assertInstanceOf(Thread::class, $this->call->thread);
        $this->assertInstanceOf(MessengerProvider::class, $this->call->owner);
        $this->assertInstanceOf(Collection::class, $this->call->participants);
    }

    /** @test */
    public function owner_returns_ghost_if_not_found()
    {
        $this->call->update([
            'owner_id' => 404,
        ]);

        $this->assertInstanceOf(GhostUser::class, $this->call->owner);
    }

    /** @test */
    public function call_type_verbose()
    {
        $this->assertSame('VIDEO', $this->call->getTypeVerbose());
    }

    /** @test */
    public function active_boolean()
    {
        $this->assertTrue($this->call->isActive());

        $this->call->update([
            'call_ended' => now(),
        ]);

        $this->assertFalse($this->call->isActive());
    }

    /** @test */
    public function is_setup_boolean()
    {
        $this->assertTrue($this->call->isSetup());

        $this->call->update([
            'setup_complete' => false,
        ]);

        $this->assertFalse($this->call->isSetup());
    }

    /** @test */
    public function is_torn_down_boolean()
    {
        $this->assertFalse($this->call->isTornDown());

        $this->call->update([
            'teardown_complete' => true,
        ]);

        $this->assertTrue($this->call->isTornDown());
    }

    /** @test */
    public function has_ended_boolean()
    {
        $this->assertFalse($this->call->hasEnded());

        $this->call->update([
            'call_ended' => now(),
        ]);

        $this->assertTrue($this->call->hasEnded());
        $this->assertSame(0, Call::active()->count());
    }

    /** @test */
    public function is_video_call_boolean()
    {
        $this->assertTrue($this->call->isVideoCall());

        $this->call->update([
            'type' => 2,
        ]);

        $this->assertFalse($this->call->isVideoCall());
    }

    /** @test */
    public function is_group_call_boolean()
    {
        $this->assertTrue($this->call->isGroupCall());
        $this->assertTrue($this->call->isGroupCall($this->group));
    }

    /** @test */
    public function is_not_group_call_boolean()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $call = $this->createCall($private, $this->tippin);

        $this->assertFalse($call->isGroupCall());
        $this->assertFalse($call->isGroupCall($private));
    }

    /** @test */
    public function it_has_thread_name()
    {
        $this->assertSame('First Test Group', $this->call->name());
        $this->assertSame('First Test Group', $this->call->name($this->group));
    }

    /** @test */
    public function it_doesnt_have_current_participant_if_provider_not_set()
    {
        $this->assertNull($this->call->currentCallParticipant());
    }

    /** @test */
    public function it_has_current_participant()
    {
        Messenger::setProvider($this->tippin);
        $participant = $this->call->currentCallParticipant();

        $this->assertSame($participant, $this->call->currentCallParticipant());
        $this->assertInstanceOf(CallParticipant::class, $participant);
        $this->assertEquals($this->tippin->getKey(), $participant->owner_id);
    }

    /** @test */
    public function it_has_no_admin_when_ended()
    {
        Messenger::setProvider($this->tippin);
        $this->call->update([
            'call_ended' => now(),
        ]);

        $this->assertFalse($this->call->isCallAdmin());
        $this->assertFalse($this->call->isCallAdmin($this->group));
    }

    /** @test */
    public function is_admin_if_call_creator()
    {
        Messenger::setProvider($this->tippin);

        $this->assertTrue($this->call->isCallAdmin());
        $this->assertTrue($this->call->isCallAdmin($this->group));
    }

    /** @test */
    public function admin_false_if__not_creator_or_group_thread_admin()
    {
        Messenger::setProvider($this->doe);

        $this->assertFalse($this->call->isCallAdmin());
        $this->assertFalse($this->call->isCallAdmin($this->group));
    }

    /** @test */
    public function is_admin_if_group_admin()
    {
        $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update(Definitions::DefaultAdminParticipant);
        Messenger::setProvider($this->doe);

        $this->assertTrue($this->call->isCallAdmin());
        $this->assertTrue($this->call->isCallAdmin($this->group));
    }

    /** @test */
    public function has_joined_false_if_not_joined()
    {
        Messenger::setProvider($this->companyDevelopers());

        $this->assertFalse($this->call->hasJoinedCall());
    }

    /** @test */
    public function has_joined_true_if_joined()
    {
        Messenger::setProvider($this->doe);

        $this->assertTrue($this->call->hasJoinedCall());
    }

    /** @test */
    public function call_participant_was_not_kicked()
    {
        Messenger::setProvider($this->doe);

        $this->assertFalse($this->call->wasKicked());
    }

    /** @test */
    public function call_participant_was_kicked()
    {
        $this->call->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'kicked' => true,
            ]);
        Messenger::setProvider($this->doe);

        $this->assertTrue($this->call->wasKicked());
    }

    /** @test */
    public function not_in_call_when_call_ended()
    {
        Messenger::setProvider($this->tippin);
        $this->call->update([
            'call_ended' => now(),
        ]);

        $this->assertFalse($this->call->isInCall());
    }

    /** @test */
    public function participant_is_in_call()
    {
        Messenger::setProvider($this->tippin);

        $this->assertTrue($this->call->isInCall());
        $this->assertFalse($this->call->hasLeftCall());
    }

    /** @test */
    public function participant_is_not_in_call()
    {
        $this->call->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first()
            ->update([
                'left_call' => now(),
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->call->isInCall());
        $this->assertTrue($this->call->hasLeftCall());
    }
}
