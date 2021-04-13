<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallParticipantTest extends FeatureTestCase
{
    private Call $call;
    private CallParticipant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $group = $this->createGroupThread($this->tippin);
        $this->call = $this->createCall($group, $this->tippin);
        $this->participant = $this->call->participants()->first();
    }

    /** @test */
    public function it_exists()
    {
        $this->assertDatabaseCount('call_participants', 1);
        $this->assertDatabaseHas('call_participants', [
            'id' => $this->participant->id,
        ]);
        $this->assertInstanceOf(CallParticipant::class, $this->participant);
        $this->assertSame(1, CallParticipant::inCall()->count());
    }

    /** @test */
    public function active_call_participant_scope_finds_none()
    {
        $this->participant->update([
            'left_call' => now(),
        ]);

        $this->assertSame(0, CallParticipant::inCall()->count());
    }

    /** @test */
    public function it_has_relations()
    {
        $this->assertSame($this->call->id, $this->participant->call->id);
        $this->assertSame($this->tippin->getKey(), $this->call->owner->getKey());
        $this->assertInstanceOf(Call::class, $this->participant->call);
        $this->assertInstanceOf(MessengerProvider::class, $this->participant->owner);
    }

    /** @test */
    public function owner_returns_ghost_if_not_found()
    {
        $this->participant->update([
            'owner_id' => 404,
        ]);

        $this->assertInstanceOf(GhostUser::class, $this->participant->owner);
    }

    /** @test */
    public function it_cast_attributes()
    {
        $this->assertInstanceOf(Carbon::class, $this->participant->created_at);
        $this->assertInstanceOf(Carbon::class, $this->participant->updated_at);
        $this->assertFalse($this->participant->kicked);
    }
}
