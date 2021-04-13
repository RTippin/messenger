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

        $this->group = $this->createGroupThread($this->tippin, $this->doe);
    }

    /** @test */
    public function user_forbidden_to_remove_participant_from_private_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        $participant = $private->participants()
            ->forProvider($this->doe)
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
            ->forProvider($this->tippin)
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
        $participant = $this->group->participants()
            ->forProvider($this->doe)
            ->first();
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            ThreadLeftBroadcast::class,
            RemovedFromThreadEvent::class,
        ]);

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
            ->forProvider($this->doe)
            ->first();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.participants.destroy', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }
}
