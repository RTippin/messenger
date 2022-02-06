<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::hit($thread->getKnockCacheKey($this->tippin), 300);

        $this->expectException(KnockException::class);

        app(SendKnock::class)->execute($thread);
    }

    /** @test */
    public function it_throws_exception_if_group_lockout_key_exist()
    {
        $thread = Thread::factory()->group()->subject('Test Group')->create();
        RateLimiter::hit($thread->getKnockCacheKey($this->tippin), 300);

        $this->expectException(KnockException::class);

        app(SendKnock::class)->execute($thread);
    }

    /** @test */
    public function it_doesnt_hit_rate_limiter_if_timeout_zero()
    {
        Messenger::setKnockTimeout(0);
        $thread = Thread::factory()->group()->create();
        $limiter = RateLimiter::spy();

        app(SendKnock::class)->execute($thread);

        $limiter->shouldNotHaveReceived('hit');
    }

    /** @test */
    public function it_hits_rate_limiter_if_timeout_not_zero()
    {
        $thread = Thread::factory()->group()->create();
        $key = $thread->getKnockCacheKey($this->tippin);
        $limiter = RateLimiter::spy();

        app(SendKnock::class)->execute($thread);

        $limiter->shouldHaveReceived('hit')->with($key, 300);
    }

    /** @test */
    public function it_hits_rate_limiter_using_custom_timeout()
    {
        Messenger::setKnockTimeout(2);
        $thread = Thread::factory()->group()->create();
        $key = $thread->getKnockCacheKey($this->tippin);
        $limiter = RateLimiter::spy();

        app(SendKnock::class)->execute($thread);

        $limiter->shouldHaveReceived('hit')->with($key, 120);
    }

    /** @test */
    public function it_fires_events_to_private_thread()
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
            $this->assertFalse($event->broadcastWith()['thread']['group']);

            return true;
        });
        Event::assertDispatched(function (KnockEvent $event) use ($thread) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);

            return true;
        });
        $this->logBroadcast(KnockBroadcast::class, 'Knock at private thread.');
    }

    /** @test */
    public function it_fires_events_to_group_thread()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            KnockBroadcast::class,
            KnockEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);

        app(SendKnock::class)->execute($thread);

        Event::assertDispatched(function (KnockBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertNotContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread']['id']);
            $this->assertTrue($event->broadcastWith()['thread']['group']);

            return true;
        });
        Event::assertDispatched(function (KnockEvent $event) use ($thread) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);

            return true;
        });
        $this->logBroadcast(KnockBroadcast::class, 'Knock at group thread.');
    }
}
