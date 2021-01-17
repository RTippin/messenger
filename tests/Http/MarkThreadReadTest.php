<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\ParticipantReadBroadcast;
use RTippin\Messenger\Events\ParticipantsReadEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MarkThreadReadTest extends FeatureTestCase
{
    private Thread $private;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $this->private = $this->createPrivateThread($tippin, $this->userDoe());

        $this->createMessage($this->private, $tippin);
    }

    /** @test */
    public function mark_read_cannot_be_a_post()
    {
        $this->actingAs($this->userTippin());

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
        $tippin = $this->userTippin();

        $this->expectsEvents([
            ParticipantReadBroadcast::class,
            ParticipantsReadEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();

        $participant = $this->private->participants()
            ->where('owner_id', '=', $tippin->getKey())
            ->where('owner_type', '=', get_class($tippin))
            ->first();

        $this->assertNotNull($participant->last_read);
    }

    /** @test */
    public function read_participant_can_mark_read_and_nothing_changes()
    {
        $tippin = $this->userTippin();

        $this->doesntExpectEvents([
            ParticipantReadBroadcast::class,
            ParticipantsReadEvent::class,
        ]);

        $this->private->participants()
            ->where('owner_id', '=', $tippin->getKey())
            ->where('owner_type', '=', get_class($tippin))
            ->first()
            ->update([
                'last_read' => now(),
            ]);

        $this->travel(5)->minutes();

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function pending_thread_awaiting_participant_approval_will_change_nothing()
    {
        $tippin = $this->userTippin();

        $this->doesntExpectEvents([
            ParticipantReadBroadcast::class,
            ParticipantsReadEvent::class,
        ]);

        $participant = $this->private->participants()
            ->where('owner_id', '=', $tippin->getKey())
            ->where('owner_type', '=', get_class($tippin))
            ->first();

        $participant->update([
            'pending' => true,
        ]);

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();

        $this->assertNull($participant->fresh()->last_read);
    }

    /** @test */
    public function pending_thread_awaiting_other_participant_approval_can_mark_read()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->expectsEvents([
            ParticipantReadBroadcast::class,
            ParticipantsReadEvent::class,
        ]);

        $this->private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first()
            ->update([
                'pending' => true,
            ]);

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();
    }
}
