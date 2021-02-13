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
    private MessengerProvider $tippin;

    private Call $call;

    private CallParticipant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $group = $this->createGroupThread($this->tippin);

        $this->call = $this->createCall($group, $this->tippin);

        $this->participant = $this->call->participants()->first();
    }

    /** @test */
    public function call_participant_exists()
    {
        $this->assertDatabaseCount('call_participants', 1);

        $this->assertDatabaseHas('call_participants', [
            'id' => $this->participant->id,
        ]);

        $this->assertInstanceOf(CallParticipant::class, $this->participant);
    }

    /** @test */
    public function call_participant_has_relations()
    {
        $this->assertSame($this->call->id, $this->participant->call->id);
        $this->assertSame($this->tippin->getKey(), $this->call->owner->getKey());
    }

    /** @test */
    public function call_participant_owner_returns_ghost_when_owner_not_found()
    {
        $this->participant->update([
            'owner_id' => 404,
        ]);

        $this->assertInstanceOf(GhostUser::class, $this->participant->owner);
    }

    /** @test */
    public function call_participant_attributes_casted()
    {
        $this->assertInstanceOf(Carbon::class, $this->participant->created_at);
        $this->assertInstanceOf(Carbon::class, $this->participant->updated_at);
        $this->assertFalse($this->participant->kicked);
    }
}
