<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\SendKnock;
use RTippin\Messenger\Broadcasting\KnockBroadcast;
use RTippin\Messenger\Events\KnockEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\KnockException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class SendKnockTest extends FeatureTestCase
{
    use BroadcastLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setKnockKnock(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Knocking is currently disabled.');

        app(SendKnock::class)->execute(Thread::factory()->create());
    }

    /** @test */
    public function it_throws_exception_if_private_lockout_key_exist()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        Cache::put("knock.knock.$thread->id.{$this->tippin->getKey()}", true);

        $this->expectException(KnockException::class);
        $this->expectExceptionMessage('You may only knock at John Doe once every 5 minutes.');

        app(SendKnock::class)->execute($thread);
    }

    /** @test */
    public function it_throws_exception_if_group_lockout_key_exist()
    {
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);
        Cache::put("knock.knock.$thread->id", true);

        $this->expectException(KnockException::class);
        $this->expectExceptionMessage('You may only knock at Test once every 5 minutes.');

        app(SendKnock::class)->execute($thread);
    }

    /** @test */
    public function it_stores_private_cache_key()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        app(SendKnock::class)->execute($thread);

        $this->assertTrue(Cache::has("knock.knock.$thread->id.{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_stores_group_cache_key()
    {
        $thread = Thread::factory()->group()->create();

        app(SendKnock::class)->execute($thread);

        $this->assertTrue(Cache::has("knock.knock.$thread->id"));
    }

    /** @test */
    public function it_doesnt_stores_private_cache_key_if_timeout_zero()
    {
        Messenger::setKnockTimeout(0);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        app(SendKnock::class)->execute($thread);

        $this->assertFalse(Cache::has("knock.knock.$thread->id.{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_doesnt_stores_group_cache_key_if_timeout_zero()
    {
        Messenger::setKnockTimeout(0);
        $thread = Thread::factory()->group()->create();

        app(SendKnock::class)->execute($thread);

        $this->assertFalse(Cache::has("knock.knock.$thread->id"));
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            KnockBroadcast::class,
            KnockEvent::class,
        ]);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        app(SendKnock::class)->execute($thread);

        Event::assertDispatched(function (KnockBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertNotContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread']['id']);

            return true;
        });
        Event::assertDispatched(function (KnockEvent $event) use ($thread) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);

            return true;
        });
        $this->logBroadcast(KnockBroadcast::class);
    }
}
