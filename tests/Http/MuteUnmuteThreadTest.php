<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Events\ParticipantMutedEvent;
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
        Event::fake([
            ParticipantMutedEvent::class,
        ]);

        $tippin = $this->userTippin();

        $participant = $this->private->participants()
            ->where('owner_id', '=', $tippin->getKey())
            ->where('owner_type', '=', get_class($tippin))
            ->first();

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.mute', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();

        $this->assertTrue($participant->fresh()->muted);

        Event::assertDispatched(function (ParticipantMutedEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
    }

    /** @test */
    public function participant_can_unmute_thread()
    {
        $tippin = $this->userTippin();

        $this->private->participants()
            ->where('owner_id', '=', $tippin->getKey())
            ->where('owner_type', '=', get_class($tippin))
            ->first()
            ->update([
                'muted' => true,
            ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.unmute', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();

        $this->assertDatabaseHas('participants', [
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'thread_id' => $this->private->id,
            'muted' => false,
        ]);
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
