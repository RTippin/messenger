<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\ParticipantReadBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ParticipantsReadEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MarkThreadReadTest extends FeatureTestCase
{
    private Thread $private;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->createMessage($this->private, $this->tippin);
    }

    /** @test */
    public function mark_read_cannot_be_a_post()
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.mark.read', [
            'thread' => $this->private->id,
        ]))
            ->assertStatus(405);
    }

    /** @test */
    public function non_participant_forbidden_to_mark_read()
    {
        $this->actingAs($this->companyDevelopers());

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function unread_participant_can_mark_read()
    {
        $this->expectsEvents([
            ParticipantReadBroadcast::class,
            ParticipantsReadEvent::class,
        ]);

        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function read_participant_can_mark_read_and_nothing_changes()
    {
        $this->doesntExpectEvents([
            ParticipantReadBroadcast::class,
            ParticipantsReadEvent::class,
        ]);

        $this->private->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first()
            ->update([
                'last_read' => now(),
            ]);

        $this->travel(5)->minutes();

        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function pending_thread_awaiting_participant_approval_will_change_nothing()
    {
        $this->doesntExpectEvents([
            ParticipantReadBroadcast::class,
            ParticipantsReadEvent::class,
        ]);

        $participant = $this->private->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first();

        $participant->update([
            'pending' => true,
        ]);

        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function pending_thread_awaiting_other_participant_approval_can_mark_read()
    {
        $this->expectsEvents([
            ParticipantReadBroadcast::class,
            ParticipantsReadEvent::class,
        ]);

        $this->private->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'pending' => true,
            ]);

        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();
    }
}
