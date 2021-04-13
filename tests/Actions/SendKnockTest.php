<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\SendKnock;
use RTippin\Messenger\Broadcasting\KnockBroadcast;
use RTippin\Messenger\Events\KnockEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\KnockException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class SendKnockTest extends FeatureTestCase
{
    private Thread $private;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setKnockKnock(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Knocking is currently disabled.');

        app(SendKnock::class)->withoutDispatches()->execute($this->private);
    }

    /** @test */
    public function it_throws_exception_if_private_lockout_key_exist()
    {
        Cache::put("knock.knock.{$this->private->id}.{$this->tippin->getKey()}", true);

        $this->expectException(KnockException::class);
        $this->expectExceptionMessage('You may only knock at John Doe once every 5 minutes.');

        app(SendKnock::class)->withoutDispatches()->execute($this->private);
    }

    /** @test */
    public function it_throws_exception_if_group_lockout_key_exist()
    {
        $group = $this->createGroupThread($this->tippin);
        Cache::put("knock.knock.{$group->id}", true);

        $this->expectException(KnockException::class);
        $this->expectExceptionMessage('You may only knock at First Test Group once every 5 minutes.');

        app(SendKnock::class)->withoutDispatches()->execute($group);
    }

    /** @test */
    public function it_stores_private_cache_key()
    {
        app(SendKnock::class)->withoutDispatches()->execute($this->private);

        $this->assertTrue(Cache::has('knock.knock.'.$this->private->id.'.'.$this->tippin->getKey()));
    }

    /** @test */
    public function it_stores_group_cache_key()
    {
        $group = $this->createGroupThread($this->tippin);

        app(SendKnock::class)->withoutDispatches()->execute($group);

        $this->assertTrue(Cache::has('knock.knock.'.$group->id));
    }

    /** @test */
    public function it_doesnt_stores_cache_key_if_timeout_zero()
    {
        Messenger::setKnockTimeout(0);

        app(SendKnock::class)->withoutDispatches()->execute($this->private);

        $this->assertFalse(Cache::has('knock.knock.'.$this->private->id.'.'.$this->tippin->getKey()));
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            KnockBroadcast::class,
            KnockEvent::class,
        ]);

        app(SendKnock::class)->execute($this->private);

        Event::assertDispatched(function (KnockBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertNotContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
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
