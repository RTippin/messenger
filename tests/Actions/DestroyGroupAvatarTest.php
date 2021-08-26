<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\DestroyGroupAvatar;
use RTippin\Messenger\Broadcasting\ThreadAvatarBroadcast;
use RTippin\Messenger\Events\ThreadAvatarEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\ThreadAvatarMessage;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class DestroyGroupAvatarTest extends FeatureTestCase
{
    use BroadcastLogger;

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setThreadAvatars(false);
        $thread = Thread::factory()->group()->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Group avatars are currently disabled.');

        app(DestroyGroupAvatar::class)->execute($thread);
    }

    /** @test */
    public function it_updates_thread()
    {
        $thread = Thread::factory()->group()->create(['image' => 'avatar.png']);

        app(DestroyGroupAvatar::class)->execute($thread);

        $this->assertDatabaseHas('threads', [
            'id' => $thread->id,
            'image' => null,
        ]);
    }

    /** @test */
    public function it_removes_avatar_from_disk()
    {
        $thread = Thread::factory()->group()->create(['image' => 'avatar.jpg']);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($thread->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);

        app(DestroyGroupAvatar::class)->execute($thread);

        Storage::disk('messenger')->assertMissing($thread->getAvatarDirectory().'/avatar.jpg');
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create(['image' => 'avatar.jpg']);
        Event::fake([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        app(DestroyGroupAvatar::class)->execute($thread);

        Event::assertDispatched(function (ThreadAvatarBroadcast $event) use ($thread) {
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());

            return true;
        });
        Event::assertDispatched(function (ThreadAvatarEvent $event) use ($thread) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);

            return true;
        });
        $this->logBroadcast(ThreadAvatarBroadcast::class, 'Removing avatar.');
    }

    /** @test */
    public function it_doesnt_fires_events_if_no_avatar_to_remove()
    {
        BaseMessengerAction::enableEvents();
        Messenger::setProvider($this->tippin);
        Event::fake([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        app(DestroyGroupAvatar::class)->execute(Thread::factory()->group()->create());

        Event::assertNotDispatched(ThreadAvatarBroadcast::class);
        Event::assertNotDispatched(ThreadAvatarEvent::class);
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create(['image' => 'avatar.jpg']);

        app(DestroyGroupAvatar::class)->execute($thread);

        Bus::assertDispatched(ThreadAvatarMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setProvider($this->tippin)->setSystemMessageSubscriber('queued', false);
        $thread = Thread::factory()->group()->create(['image' => 'avatar.jpg']);

        app(DestroyGroupAvatar::class)->execute($thread);

        Bus::assertDispatchedSync(ThreadAvatarMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setProvider($this->tippin)->setSystemMessageSubscriber('enabled', false);
        $thread = Thread::factory()->group()->create(['image' => 'avatar.jpg']);

        app(DestroyGroupAvatar::class)->execute($thread);

        Bus::assertNotDispatched(ThreadAvatarMessage::class);
    }
}
