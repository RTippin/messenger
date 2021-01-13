<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
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

        Event::fake([
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

        Event::assertDispatched(function (ParticipantReadBroadcast $event) use ($tippin) {
            $this->assertContains('private-user.'.$tippin->getKey(), $event->broadcastOn());
            $this->assertSame($this->private->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ParticipantsReadEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
    }

    /** @test */
    public function read_participant_can_mark_read_and_nothing_changes()
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
            'last_read' => now(),
        ]);

        $this->travel(5)->minutes();

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();

        $this->assertSame($participant->last_read->toDayDateTimeString(), $participant->fresh()->last_read->toDayDateTimeString());
    }
}
