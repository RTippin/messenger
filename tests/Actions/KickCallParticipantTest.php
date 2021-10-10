<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Calls\KickCallParticipant;
use RTippin\Messenger\Broadcasting\KickedFromCallBroadcast;
use RTippin\Messenger\Events\KickedFromCallEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class KickCallParticipantTest extends FeatureTestCase
{
    use BroadcastLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_updates_participant_kicked_true_and_removes_participant_cache_key()
    {
        $call = Call::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->doe)->create();
        Carbon::setTestNow($left = now()->addMinutes(5));
        $participant->setParticipantInCallCache();

        $this->assertTrue($participant->isParticipantInCallCache());

        app(KickCallParticipant::class)->execute($call, $participant, true);

        $this->assertFalse($participant->isParticipantInCallCache());
        $this->assertDatabaseHas('call_participants', [
            'id' => $participant->id,
            'kicked' => true,
            'left_call' => $left,
        ]);
    }

    /** @test */
    public function it_updates_participant_kicked_false()
    {
        $call = Call::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->doe)->left()->kicked()->create();

        app(KickCallParticipant::class)->execute($call, $participant, false);

        $this->assertDatabaseHas('call_participants', [
            'id' => $participant->id,
            'kicked' => false,
        ]);
    }

    /** @test */
    public function it_fires_kicked_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            KickedFromCallBroadcast::class,
            KickedFromCallEvent::class,
        ]);
        $call = Call::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->doe)->create();

        app(KickCallParticipant::class)->execute($call, $participant, true);

        Event::assertDispatched(function (KickedFromCallBroadcast $event) use ($call) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($call->id, $event->broadcastWith()['call_id']);
            $this->assertTrue($event->broadcastWith()['kicked']);

            return true;
        });
        Event::assertDispatched(function (KickedFromCallEvent $event) use ($call, $participant) {
            $this->assertSame($call->id, $event->call->id);
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($participant->id, $event->participant->id);

            return true;
        });
        $this->logBroadcast(KickedFromCallBroadcast::class, 'Participant was kicked.');
    }

    /** @test */
    public function it_fires_un_kicked_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            KickedFromCallBroadcast::class,
            KickedFromCallEvent::class,
        ]);
        $call = Call::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->doe)->kicked()->create();

        app(KickCallParticipant::class)->execute($call, $participant, false);

        Event::assertDispatched(function (KickedFromCallBroadcast $event) use ($call) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($call->id, $event->broadcastWith()['call_id']);
            $this->assertFalse($event->broadcastWith()['kicked']);

            return true;
        });
        Event::assertDispatched(function (KickedFromCallEvent $event) use ($call, $participant) {
            $this->assertSame($call->id, $event->call->id);
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($participant->id, $event->participant->id);

            return true;
        });
        $this->logBroadcast(KickedFromCallBroadcast::class, 'Participant was un-kicked.');
    }
}
