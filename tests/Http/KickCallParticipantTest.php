<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\KickedFromCallBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
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

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);

        $this->call = $this->createCall($this->group, $this->tippin);

        $this->participant = $this->call->participants()->create([
            'owner_id' => $this->doe->getKey(),
            'owner_type' => get_class($this->doe),
        ]);
    }

    /** @test */
    public function kick_participant_must_be_an_update()
    {
        $this->actingAs($this->tippin);

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
        $this->actingAs($this->tippin);

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
        $this->call->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first()
            ->update([
                'left_call' => now(),
            ]);

        $this->actingAs($this->tippin);

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
        $this->actingAs($this->doe);

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
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $call = $this->createCall($private, $this->tippin, $this->doe);

        $participant = $call->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        $this->actingAs($this->tippin);

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

        $this->actingAs($this->tippin);

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

        Event::assertDispatched(function (KickedFromCallBroadcast $event) {
            $this->assertContains('private-user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->call->id, $event->broadcastWith()['call_id']);
            $this->assertTrue($event->broadcastWith()['kicked']);

            return true;
        });

        Event::assertDispatched(function (KickedFromCallEvent $event) {
            $this->assertSame($this->call->id, $event->call->id);
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->participant->id, $event->participant->id);

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

        $call = $this->createCall($this->group, $this->doe, $this->tippin);

        $participant = $call->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first();

        $this->actingAs($this->doe);

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

        $this->participant->update([
            'kicked' => true,
            'left_call' => now(),
        ]);

        $this->actingAs($this->tippin);

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
