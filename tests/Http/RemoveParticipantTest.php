<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Events\RemovedFromThreadEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class RemoveParticipantTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread(
            $this->userTippin(),
            $this->userDoe(),
            $this->companyDevelopers()
        );
    }

    /** @test */
    public function user_forbidden_to_remove_participant_from_private_thread()
    {
        $doe = $this->userDoe();

        $tippin = $this->userTippin();

        $private = $this->createPrivateThread($tippin, $doe);

        $participant = $private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($this->userTippin());

        $this->deleteJson(route('api.messenger.threads.participants.destroy', [
            'thread' => $private->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_forbidden_to_remove_participant()
    {
        $tippin = $this->userTippin();

        $participant = $this->group->participants()
            ->where('owner_id', '=', $tippin->getKey())
            ->where('owner_type', '=', get_class($tippin))
            ->first();

        $this->actingAs($this->userDoe());

        $this->deleteJson(route('api.messenger.threads.participants.destroy', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_remove_participant()
    {
        $doe = $this->userDoe();

        $tippin = $this->userTippin();

        $this->expectsEvents([
            ThreadLeftBroadcast::class,
            RemovedFromThreadEvent::class,
        ]);

        $participant = $this->group->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($tippin);

        $this->deleteJson(route('api.messenger.threads.participants.destroy', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful();

        $this->assertSoftDeleted('participants', [
            'id' => $participant->id,
        ]);
    }
}
