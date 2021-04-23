<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Calls\JoinCall;
use RTippin\Messenger\Broadcasting\CallJoinedBroadcast;
use RTippin\Messenger\Events\CallJoinedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class JoinCallTest extends FeatureTestCase
{
    private Thread $group;
    private Call $call;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);
        $this->call = $this->createCall($this->group, $this->tippin);
    }

    /** @test */
    public function it_stores_call_participant()
    {
        Messenger::setProvider($this->doe);

        app(JoinCall::class)->withoutDispatches()->execute($this->call);

        $this->assertDatabaseCount('call_participants', 2);
        $this->assertDatabaseHas('call_participants', [
            'call_id' => $this->call->id,
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'left_call' => null,
        ]);
    }

    /** @test */
    public function it_stores_call_participant_cache_key()
    {
        Messenger::setProvider($this->doe);

        app(JoinCall::class)->withoutDispatches()->execute($this->call);

        $participant = $this->call->participants()->forProvider($this->doe)->first();

        $this->assertTrue(Cache::has("call:{$this->call->id}:{$participant->id}"));
    }

    /** @test */
    public function it_fires_no_events_or_stores_cache_key_if_already_joined()
    {
        Messenger::setProvider($this->tippin);

        $this->doesntExpectEvents([
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);

        $participant = $this->call->participants()->first();

        app(JoinCall::class)->execute($this->call);

        $this->assertFalse(Cache::has("call:{$this->call->id}:{$participant->id}"));
    }

    /** @test */
    public function it_updates_participant_and_cache_if_rejoining()
    {
        $participant = $this->call->participants()->first();
        $participant->update([
            'left_call' => now(),
        ]);
        Messenger::setProvider($this->tippin);

        app(JoinCall::class)->withoutDispatches()->execute($this->call);

        $this->assertTrue(Cache::has("call:{$this->call->id}:{$participant->id}"));
        $this->assertDatabaseHas('call_participants', [
            'id' => $participant->id,
            'left_call' => null,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Messenger::setProvider($this->doe);
        Event::fake([
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);

        app(JoinCall::class)->execute($this->call);

        $participant = $this->call->participants()->forProvider($this->doe)->first();

        Event::assertDispatched(function (CallJoinedBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->call->id, $event->broadcastWith()['id']);
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (CallJoinedEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
    }
}
