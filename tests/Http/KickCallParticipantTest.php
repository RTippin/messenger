<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class KickCallParticipantTest extends FeatureTestCase
{
    private Thread $group;

    private Call $call;

    private CallParticipant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->group = $this->createGroupThread(
            $tippin,
            $this->userDoe()
        );

        $this->call = $this->createCall(
            $this->group,
            $tippin
        );

        $this->participant = $this->call->participants()->create([
            'owner_id' => $doe->getKey(),
            'owner_type' => get_class($doe),
        ]);
    }

    /** @test */
    public function kick_participant_must_be_an_update()
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
            'participant' => $this->participant->id,
        ]), [
            'kicked' => true,
        ])
            ->assertStatus(405);
    }

    /** @test */
    public function kick_participant_on_missing_participant_not_found()
    {
        $this->actingAs($this->userTippin());

        $this->putJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
            'participant' => '123-456-789',
        ]), [
            'kicked' => true,
        ])
            ->assertNotFound();
    }
}
