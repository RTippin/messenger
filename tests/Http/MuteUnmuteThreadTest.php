<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Events\ParticipantMutedEvent;
use RTippin\Messenger\Events\ParticipantUnMutedEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MuteUnmuteThreadTest extends FeatureTestCase
{
    private Thread $private;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->createPrivateThread(
            $this->userTippin(),
            $this->userDoe()
        );
    }

    /** @test */
    public function non_participant_forbidden_to_mute_thread()
    {
        $this->actingAs($this->companyDevelopers());

        $this->postJson(route('api.messenger.threads.mute', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_unmute_thread()
    {
        $this->actingAs($this->companyDevelopers());

        $this->postJson(route('api.messenger.threads.unmute', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_can_mute_thread()
    {
        $this->expectsEvents([
            ParticipantMutedEvent::class,
        ]);

        $tippin = $this->userTippin();

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.mute', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();

        $participant = $this->private->participants()
            ->where('owner_id', '=', $tippin->getKey())
            ->where('owner_type', '=', get_class($tippin))
            ->first();

        $this->assertTrue($participant->muted);

    }

    /** @test */
    public function participant_can_unmute_thread()
    {
        Event::fake([
            ParticipantUnMutedEvent::class,
        ]);

        $tippin = $this->userTippin();

        $participant = $this->private->participants()
            ->where('owner_id', '=', $tippin->getKey())
            ->where('owner_type', '=', get_class($tippin))
            ->first();

        $participant->update([
            'muted' => true,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.unmute', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();

        $this->assertFalse($participant->fresh()->muted);

        Event::assertDispatched(function (ParticipantUnMutedEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
    }

    /** @test */
    public function mute_thread_updates_nothing_if_already_muted()
    {
        $this->doesntExpectEvents([
            ParticipantMutedEvent::class,
        ]);

        $tippin = $this->userTippin();

        $this->private->participants()
            ->where('owner_id', '=', $tippin->getKey())
            ->where('owner_type', '=', get_class($tippin))
            ->first()
            ->update([
                'muted' => true,
            ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.mute', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function unmute_thread_updates_nothing_if_not_muted()
    {
        $this->doesntExpectEvents([
            ParticipantUnMutedEvent::class,
        ]);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.unmute', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function muted_participant_receives_no_broadcast()
    {
        $doe = $this->userDoe();

        $tippin = $this->userTippin();

        $this->private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first()
            ->update([
                'muted' => true,
            ]);

        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();

        Event::assertDispatched(function (NewMessageBroadcast $event) use ($doe, $tippin) {
            $this->assertNotContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$tippin->getKey(), $event->broadcastOn());

            return true;
        });

        Event::assertDispatched(NewMessageEvent::class);
    }
}
