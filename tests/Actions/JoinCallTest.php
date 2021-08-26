<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Calls\JoinCall;
use RTippin\Messenger\Broadcasting\CallJoinedBroadcast;
use RTippin\Messenger\Events\CallJoinedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class JoinCallTest extends FeatureTestCase
{
    use BroadcastLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_stores_call_participant()
    {
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();

        app(JoinCall::class)->execute($call);

        $this->assertDatabaseCount('call_participants', 1);
        $this->assertDatabaseHas('call_participants', [
            'call_id' => $call->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'left_call' => null,
        ]);
    }

    /** @test */
    public function it_stores_call_participant_cache_key()
    {
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();

        app(JoinCall::class)->execute($call);

        $participant = CallParticipant::first();

        $this->assertTrue(Cache::has("call:$call->id:$participant->id"));
    }

    /** @test */
    public function it_fires_no_events_or_stores_cache_key_if_already_joined()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->tippin)->create();

        app(JoinCall::class)->execute($call);

        $this->assertFalse(Cache::has("call:$call->id:$participant->id"));

        Event::assertNotDispatched(CallJoinedBroadcast::class);
        Event::assertNotDispatched(CallJoinedEvent::class);
    }

    /** @test */
    public function it_updates_participant_and_cache_if_rejoining()
    {
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->tippin)->left()->create();

        app(JoinCall::class)->execute($call);

        $this->assertTrue(Cache::has("call:$call->id:$participant->id"));
        $this->assertDatabaseHas('call_participants', [
            'id' => $participant->id,
            'left_call' => null,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();

        app(JoinCall::class)->execute($call);

        $participant = CallParticipant::first();

        Event::assertDispatched(function (CallJoinedBroadcast $event) use ($thread, $call) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($call->id, $event->broadcastWith()['id']);
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (CallJoinedEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
        $this->logBroadcast(CallJoinedBroadcast::class);
    }
}
