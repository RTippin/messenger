<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\RemovedFromThreadEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class RemoveParticipantTest extends FeatureTestCase
{
    private Thread $group;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);
    }

    /** @test */
    public function user_forbidden_to_remove_participant_from_private_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $participant = $private->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.participants.destroy', [
            'thread' => $private->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_forbidden_to_remove_participant()
    {
        $participant = $this->group->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first();

        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.participants.destroy', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_remove_participant()
    {
        $this->expectsEvents([
            ThreadLeftBroadcast::class,
            RemovedFromThreadEvent::class,
        ]);

        $participant = $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.participants.destroy', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_forbidden_to_remove_participant_when_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);

        $participant = $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.participants.destroy', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }
}
