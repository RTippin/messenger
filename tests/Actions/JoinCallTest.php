<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Calls\JoinCall;
use RTippin\Messenger\Broadcasting\CallJoinedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\CallJoinedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class JoinCallTest extends FeatureTestCase
{
    private Thread $group;

    private Call $call;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);

        $this->call = $this->createCall($this->group, $this->tippin);
    }

    /** @test */
    public function join_call_stores_call_participant()
    {
        Messenger::setProvider($this->doe);

        app(JoinCall::class)->withoutDispatches()->execute($this->call);

        $this->assertDatabaseCount('call_participants', 2);

        $this->assertDatabaseHas('call_participants', [
            'call_id' => $this->call->id,
            'owner_id' => $this->doe->getKey(),
            'owner_type' => get_class($this->doe),
            'left_call' => null,
        ]);
    }

    /** @test */
    public function join_call_sets_participant_in_cache()
    {
        Messenger::setProvider($this->doe);

        app(JoinCall::class)->withoutDispatches()->execute($this->call);

        $participant = $this->call->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        $this->assertTrue(Cache::has("call:{$this->call->id}:{$participant->id}"));
    }

    /** @test */
    public function join_call_fires_events()
    {
        Messenger::setProvider($this->doe);

        Event::fake([
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);

        app(JoinCall::class)->execute($this->call);

        $participant = $this->call->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        Event::assertDispatched(function (CallJoinedBroadcast $event) {
            $this->assertContains('private-user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->call->id, $event->broadcastWith()['id']);
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (CallJoinedEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
    }
}
