<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\DemotedAdminBroadcast;
use RTippin\Messenger\Broadcasting\PromotedAdminBroadcast;
use RTippin\Messenger\Events\DemotedAdminEvent;
use RTippin\Messenger\Events\PromotedAdminEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PromoteDemoteParticipantTest extends FeatureTestCase
{
    private Thread $private;

    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->group = $this->createGroupThread($tippin, $doe, $this->companyDevelopers());

        $this->private = $this->createPrivateThread($tippin, $doe);
    }

    /** @test */
    public function user_forbidden_to_promote_admin_role_in_private_thread()
    {
        $doe = $this->userDoe();

        $participant = $this->private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $this->private->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_forbidden_to_demote_admin_role_in_private_thread()
    {
        $doe = $this->userDoe();

        $participant = $this->private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $this->private->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_forbidden_to_demote_admin()
    {
        $tippin = $this->userTippin();

        $participant = $this->group->participants()
            ->where('owner_id', '=', $tippin->getKey())
            ->where('owner_type', '=', get_class($tippin))
            ->first();

        $this->actingAs($this->userDoe());

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_forbidden_to_promote_admin()
    {
        $tippin = $this->userTippin();

        $participant = $this->group->participants()
            ->where('owner_id', '=', $tippin->getKey())
            ->where('owner_type', '=', get_class($tippin))
            ->first();

        $this->actingAs($this->userDoe());

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_promote_existing_admin()
    {
        $tippin = $this->userTippin();

        $participant = $this->group->participants()
            ->where('owner_id', '=', $tippin->getKey())
            ->where('owner_type', '=', get_class($tippin))
            ->first();

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $this->private->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_demote_non_admin()
    {
        $doe = $this->userDoe();

        $participant = $this->group->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $this->private->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_promote_participant_to_admin()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            PromotedAdminBroadcast::class,
            PromotedAdminEvent::class,
        ]);

        $participant = $this->group->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $participant->id,
                'admin' => true,
            ]);

        Event::assertDispatched(function (PromotedAdminBroadcast $event) use ($doe) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (PromotedAdminEvent $event) use ($tippin, $participant) {
            $this->assertSame($tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->group->id, $event->thread->id);
            $this->assertSame($participant->id, $event->participant->id);

            return true;
        });
    }

    /** @test */
    public function admin_can_demote_admin()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            DemotedAdminBroadcast::class,
            DemotedAdminEvent::class,
        ]);

        $participant = $this->group->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $participant->update([
            'admin' => true,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $participant->id,
                'admin' => false,
            ]);

        Event::assertDispatched(function (DemotedAdminBroadcast $event) use ($doe) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (DemotedAdminEvent $event) use ($tippin, $participant) {
            $this->assertSame($tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->group->id, $event->thread->id);
            $this->assertSame($participant->id, $event->participant->id);

            return true;
        });
    }
}
