<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\MarkParticipantRead;
use RTippin\Messenger\Broadcasting\ParticipantReadBroadcast;
use RTippin\Messenger\Events\ParticipantReadEvent;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class MarkParticipantReadTest extends FeatureTestCase
{
    use BroadcastLogger;

    /** @test */
    public function it_updates_participant_and_clears_last_seen_cache_key()
    {
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();
        $read = now()->addMinutes(5)->format('Y-m-d H:i:s.u');
        $cache = Cache::spy();
        Carbon::setTestNow($read);

        app(MarkParticipantRead::class)->execute($participant, $thread);

        $cache->shouldHaveReceived('forget')->with('participant:'.$participant->id.':last:read:message');
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'last_read' => $read,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ParticipantReadBroadcast::class,
            ParticipantReadEvent::class,
        ]);
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();

        app(MarkParticipantRead::class)->execute($participant, $thread);

        Event::assertDispatched(function (ParticipantReadBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (ParticipantReadEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
        $this->logBroadcast(ParticipantReadBroadcast::class);
    }

    /** @test */
    public function it_fires_no_events_if_participant_already_up_to_date()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ParticipantReadBroadcast::class,
            ParticipantReadEvent::class,
        ]);
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->read()->create();

        app(MarkParticipantRead::class)->execute($participant, $thread);

        Event::assertNotDispatched(ParticipantReadBroadcast::class);
        Event::assertNotDispatched(ParticipantReadEvent::class);
    }

    /** @test */
    public function it_does_not_update_a_pending_participant()
    {
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->pending()->create();

        app(MarkParticipantRead::class)->execute($participant, $thread);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'last_read' => null,
        ]);
    }

    /** @test */
    public function it_updates_participant_if_no_thread_supplied()
    {
        $participant = Participant::factory()->for(Thread::factory()->create())->owner($this->tippin)->create();
        $read = now()->addMinutes(5)->format('Y-m-d H:i:s.u');
        Carbon::setTestNow($read);

        app(MarkParticipantRead::class)->execute($participant);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'last_read' => $read,
        ]);
    }
}
