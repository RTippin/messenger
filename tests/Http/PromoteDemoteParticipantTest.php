<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\DemotedAdminBroadcast;
use RTippin\Messenger\Broadcasting\PromotedAdminBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\DemotedAdminEvent;
use RTippin\Messenger\Events\PromotedAdminEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PromoteDemoteParticipantTest extends FeatureTestCase
{
    private Thread $private;

    private Thread $group;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);
    }

    /** @test */
    public function user_forbidden_to_promote_admin_role_in_private_thread()
    {
        $participant = $this->private->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $this->private->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_forbidden_to_demote_admin_role_in_private_thread()
    {
        $participant = $this->private->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $this->private->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_forbidden_to_demote_admin()
    {
        $participant = $this->group->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first();

        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_forbidden_to_promote_admin()
    {
        $participant = $this->group->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first();

        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_promote_existing_admin()
    {
        $participant = $this->group->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first();

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $this->private->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_demote_non_admin()
    {
        $participant = $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $this->private->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_promote_participant_to_admin()
    {
        $this->expectsEvents([
            PromotedAdminBroadcast::class,
            PromotedAdminEvent::class,
        ]);

        $participant = $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $participant->id,
                'admin' => true,
            ]);
    }

    /** @test */
    public function admin_forbidden_to_promote_participant_to_admin_when_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);

        $participant = $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_demote_admin()
    {
        $this->expectsEvents([
            DemotedAdminBroadcast::class,
            DemotedAdminEvent::class,
        ]);

        $participant = $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        $participant->update([
            'admin' => true,
        ]);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $participant->id,
                'admin' => false,
            ]);
    }

    /** @test */
    public function admin_forbidden_to_demote_admin_when_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);

        $participant = $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        $participant->update([
            'admin' => true,
        ]);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }
}
