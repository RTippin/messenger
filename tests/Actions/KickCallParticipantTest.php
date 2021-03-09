<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Calls\KickCallParticipant;
use RTippin\Messenger\Broadcasting\KickedFromCallBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\KickedFromCallEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Tests\FeatureTestCase;

class KickCallParticipantTest extends FeatureTestCase
{
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
            'left_call' => null,
        ]);
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_updates_participant_kicked_true()
    {
        $left = now()->addMinutes(5);
        Carbon::setTestNow($left);

        app(KickCallParticipant::class)->withoutDispatches()->execute(
            $this->call,
            $this->participant,
            true
        );

        $this->assertDatabaseHas('call_participants', [
            'id' => $this->participant->id,
            'kicked' => true,
            'left_call' => $left,
        ]);
    }

    /** @test */
    public function it_updates_participant_kicked_false()
    {
        $left = now()->addMinutes(5);
        Carbon::setTestNow($left);
        $this->participant->update([
            'kicked' => true,
            'left_call' => $left,
        ]);

        app(KickCallParticipant::class)->withoutDispatches()->execute(
            $this->call,
            $this->participant,
            false
        );

        $this->assertDatabaseHas('call_participants', [
            'id' => $this->participant->id,
            'kicked' => false,
            'left_call' => $left,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            KickedFromCallBroadcast::class,
            KickedFromCallEvent::class,
        ]);

        app(KickCallParticipant::class)->execute(
            $this->call,
            $this->participant,
            true
        );

        Event::assertDispatched(function (KickedFromCallBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
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
}
