<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\KickedFromCallBroadcast;
use RTippin\Messenger\Events\KickedFromCallEvent;
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

    /** @test */
    public function non_call_participant_forbidden_to_kick_call_participant()
    {
        $tippin = $this->userTippin();

        $this->call->participants()
            ->where('owner_id', '=', $tippin->getKey())
            ->where('owner_type', '=', get_class($tippin))
            ->first()
            ->update([
                'left_call' => now(),
            ]);

        $this->actingAs($tippin);

        $this->putJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
            'participant' => $this->participant->id,
        ]), [
            'kicked' => true,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function non_call_admin_participant_forbidden_to_kick_call_participant()
    {
        $this->actingAs($this->userDoe());

        $this->putJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
            'participant' => $this->participant->id,
        ]), [
            'kicked' => true,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_kick_call_participant_in_private_thread()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $private = $this->createPrivateThread($tippin, $doe);

        $call = $this->createCall($private, $tippin, $doe);

        $participant = $call->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($tippin);

        $this->putJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $private->id,
            'call' => $call->id,
            'participant' => $participant->id,
        ]), [
            'kicked' => true,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_kick_call_participant()
    {
        Event::fake([
            KickedFromCallBroadcast::class,
            KickedFromCallEvent::class,
        ]);

        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->actingAs($tippin);

        $this->putJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
            'participant' => $this->participant->id,
        ]), [
            'kicked' => true,
        ])
            ->assertSuccessful();

        $participant = $this->participant->fresh();

        $this->assertTrue($participant->kicked);

        $this->assertNotNull($participant->left_call);

        Event::assertDispatched(function (KickedFromCallBroadcast $event) use ($doe) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertEquals($this->call->id, $event->broadcastWith()['call_id']);
            $this->assertTrue($event->broadcastWith()['kicked']);

            return true;
        });

        Event::assertDispatched(function (KickedFromCallEvent $event) use ($tippin) {
            $this->assertEquals($this->call->id, $event->call->id);
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals($this->participant->id, $event->participant->id);

            return true;
        });
    }

    /** @test */
    public function call_creator_can_kick_call_participant()
    {
        $this->expectsEvents([
            KickedFromCallBroadcast::class,
            KickedFromCallEvent::class,
        ]);

        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $call = $this->createCall($this->group, $doe, $tippin);

        $participant = $call->participants()
            ->where('owner_id', '=', $tippin->getKey())
            ->where('owner_type', '=', get_class($tippin))
            ->first();

        $this->actingAs($doe);

        $this->putJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $this->group->id,
            'call' => $call->id,
            'participant' => $participant->id,
        ]), [
            'kicked' => true,
        ])
            ->assertSuccessful();

        $participantFresh = $participant->fresh();

        $this->assertTrue($participantFresh->kicked);

        $this->assertNotNull($participantFresh->left_call);
    }

    /** @test */
    public function admin_can_un_kick_call_participant()
    {
        Event::fake([
            KickedFromCallBroadcast::class,
            KickedFromCallEvent::class,
        ]);

        $tippin = $this->userTippin();

        $this->participant->update([
            'kicked' => true,
            'left_call' => now(),
        ]);

        $this->actingAs($tippin);

        $this->putJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
            'participant' => $this->participant->id,
        ]), [
            'kicked' => false,
        ])
            ->assertSuccessful();

        $this->assertFalse($this->participant->fresh()->kicked);

        Event::assertDispatched(function (KickedFromCallBroadcast $event) {
            return $event->broadcastWith()['kicked'] === false;
        });

        Event::assertDispatched(function (KickedFromCallEvent $event) {
            return $this->participant->id === $event->participant->id;
        });
    }
}
