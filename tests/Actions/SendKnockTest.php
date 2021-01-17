<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\SendKnock;
use RTippin\Messenger\Broadcasting\KnockBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\KnockEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class SendKnockTest extends FeatureTestCase
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

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function send_knock_stores_private_thread_cache_key()
    {
        app(SendKnock::class)->withoutDispatches()->execute($this->private);

        $this->assertTrue(Cache::has('knock.knock.'.$this->private->id.'.'.$this->tippin->getKey()));
    }

    /** @test */
    public function send_knock_stores_group_thread_cache_key()
    {
        $group = $this->createGroupThread($this->tippin);

        app(SendKnock::class)->withoutDispatches()->execute($group);

        $this->assertTrue(Cache::has('knock.knock.'.$group->id));
    }

    /** @test */
    public function send_knock_stores_no_cache_lockout_when_timeout_zero_in_config()
    {
        Messenger::setKnockTimeout(0);

        app(SendKnock::class)->withoutDispatches()->execute($this->private);

        $this->assertFalse(Cache::has('knock.knock.'.$this->private->id.'.'.$this->tippin->getKey()));
    }

    /** @test */
    public function send_knock_fires_events()
    {
        Event::fake([
            KnockBroadcast::class,
            KnockEvent::class,
        ]);

        app(SendKnock::class)->execute($this->private);

        Event::assertDispatched(function (KnockBroadcast $event) {
            $this->assertContains('private-user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertNotContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($this->private->id, $event->broadcastWith()['thread']['id']);

            return true;
        });

        Event::assertDispatched(function (KnockEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->private->id, $event->thread->id);

            return true;
        });
    }
}
