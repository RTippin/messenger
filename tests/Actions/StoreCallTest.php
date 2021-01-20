<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Calls\StoreCall;
use RTippin\Messenger\Broadcasting\CallJoinedBroadcast;
use RTippin\Messenger\Broadcasting\CallStartedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\CallJoinedEvent;
use RTippin\Messenger\Events\CallStartedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreCallTest extends FeatureTestCase
{
    private Thread $private;

    private Call $call;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function store_call_stores_call_and_participant()
    {
        app(StoreCall::class)->withoutDispatches()->execute($this->private);

        $this->assertDatabaseHas('calls', [
            'thread_id' => $this->private->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'setup_complete' => false,
        ]);

        $this->assertDatabaseHas('call_participants', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'left_call' => null,
        ]);
    }

    /** @test */
    public function store_call_stores_call_with_setup_true()
    {
        app(StoreCall::class)->withoutDispatches()->execute(
            $this->private,
            true
        );

        $this->assertDatabaseHas('calls', [
            'thread_id' => $this->private->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'setup_complete' => true,
        ]);
    }

    /** @test */
    public function store_call_throws_exception_when_cache_lockout_key_exist()
    {
        Cache::put("call:{$this->private->id}:starting", true);

        $this->expectException(AuthorizationException::class);

        app(StoreCall::class)->withoutDispatches()->execute($this->private);
    }

    /** @test */
    public function store_call_throws_exception_when_active_call_exist_on_thread()
    {
        $this->createCall($this->private, $this->tippin);

        $this->expectException(AuthorizationException::class);

        app(StoreCall::class)->withoutDispatches()->execute($this->private);
    }

    /** @test */
    public function store_call_fires_events()
    {
        Event::fake([
            CallStartedBroadcast::class,
            CallStartedEvent::class,
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);

        app(StoreCall::class)->execute($this->private);

        Event::assertNotDispatched(CallJoinedBroadcast::class);

        Event::assertNotDispatched(CallJoinedEvent::class);

        Event::assertDispatched(function (CallStartedBroadcast $event) {
            $this->assertNotContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->private->id, $event->broadcastWith()['call']['thread_id']);

            return true;
        });

        Event::assertDispatched(function (CallStartedEvent $event) {
            return $this->private->id === $event->call->thread_id;
        });
    }
}
