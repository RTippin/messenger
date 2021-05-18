<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallParticipantTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $participant = CallParticipant::factory()->for(
            Call::factory()->for(
                Thread::factory()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertDatabaseCount('call_participants', 1);
        $this->assertDatabaseHas('call_participants', [
            'id' => $participant->id,
        ]);
        $this->assertInstanceOf(CallParticipant::class, $participant);
        $this->assertSame(1, CallParticipant::inCall()->count());
    }

    /** @test */
    public function active_call_participant_scope_finds_none()
    {
        CallParticipant::factory()->for(
            Call::factory()->for(
                Thread::factory()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->left()->create();

        $this->assertSame(0, CallParticipant::inCall()->count());
    }

    /** @test */
    public function it_has_relations()
    {
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->tippin)->create();

        $this->assertSame($call->id, $participant->call->id);
        $this->assertSame($this->tippin->getKey(), $call->owner->getKey());
        $this->assertInstanceOf(Call::class, $participant->call);
        $this->assertInstanceOf(MessengerProvider::class, $participant->owner);
    }

    /** @test */
    public function owner_returns_ghost_if_not_found()
    {
        $participant = CallParticipant::factory()->for(
            Call::factory()->for(
                Thread::factory()->create()
            )->owner($this->tippin)->create()
        )->create([
            'owner_id' => 404,
            'owner_type' => $this->tippin->getMorphClass(),
        ]);

        $this->assertInstanceOf(GhostUser::class, $participant->owner);
    }

    /** @test */
    public function it_cast_attributes()
    {
        $participant = CallParticipant::factory()->for(
            Call::factory()->for(
                Thread::factory()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertInstanceOf(Carbon::class, $participant->created_at);
        $this->assertInstanceOf(Carbon::class, $participant->updated_at);
        $this->assertFalse($participant->kicked);
    }
}
