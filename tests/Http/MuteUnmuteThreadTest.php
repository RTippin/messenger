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

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);
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
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            ParticipantMutedEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.mute', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function forbidden_to_mute_thread_if_locked()
    {
        $this->doe->delete();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.mute', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_unmute_thread_if_locked()
    {
        $this->doe->delete();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.unmute', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_mute_group_thread_if_locked()
    {
        $group = $this->createGroupThread($this->tippin);
        $group->update([
            'lockout' => true,
        ]);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.mute', [
            'thread' => $group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_unmute_group_thread_if_locked()
    {
        $group = $this->createGroupThread($this->tippin);
        $group->update([
            'lockout' => true,
        ]);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.unmute', [
            'thread' => $group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_can_unmute_thread()
    {
        $this->private->participants()
            ->forProvider($this->tippin)
            ->first()
            ->update([
                'muted' => true,
            ]);
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            ParticipantUnMutedEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.unmute', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function mute_thread_updates_nothing_if_already_muted()
    {
        $this->private->participants()
            ->forProvider($this->tippin)
            ->first()
            ->update([
                'muted' => true,
            ]);
        $this->actingAs($this->tippin);

        $this->doesntExpectEvents([
            ParticipantMutedEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.mute', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function unmute_thread_updates_nothing_if_not_muted()
    {
        $this->actingAs($this->tippin);

        $this->doesntExpectEvents([
            ParticipantUnMutedEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.unmute', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function muted_participant_receives_no_broadcast()
    {
        $this->private->participants()
            ->forProvider($this->doe)
            ->first()
            ->update([
                'muted' => true,
            ]);
        $this->actingAs($this->tippin);

        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();

        Event::assertDispatched(function (NewMessageBroadcast $event) {
            $this->assertNotContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());

            return true;
        });
        Event::assertDispatched(NewMessageEvent::class);
    }
}
