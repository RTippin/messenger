<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\UpdateGroupAvatar;
use RTippin\Messenger\Broadcasting\ThreadAvatarBroadcast;
use RTippin\Messenger\Events\ThreadAvatarEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\ThreadAvatarMessage;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateGroupAvatarTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setThreadAvatarUpload(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Group avatar uploads are currently disabled.');

        app(UpdateGroupAvatar::class)->execute(Thread::factory()->group()->create(), [
            'image' => UploadedFile::fake()->image('picture.jpg'),
        ]);
    }

    /** @test */
    public function it_updates_default_thread_avatar()
    {
        $thread = Thread::factory()->group()->create(['image' => '5.png']);

        app(UpdateGroupAvatar::class)->execute($thread, [
            'default' => '1.png',
        ]);

        $this->assertDatabaseHas('threads', [
            'id' => $thread->id,
            'type' => 2,
            'image' => '1.png',
        ]);
    }

    /** @test */
    public function it_updates_default_and_removes_existing_avatar_from_disk()
    {
        $thread = Thread::factory()->group()->create(['image' => 'avatar.jpg']);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($thread->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);

        app(UpdateGroupAvatar::class)->execute($thread, [
            'default' => '3.png',
        ]);

        $this->assertDatabaseHas('threads', [
            'id' => $thread->id,
            'type' => 2,
            'image' => '3.png',
        ]);
        Storage::disk('messenger')->assertMissing($thread->getAvatarDirectory().'/avatar.jpg');
    }

    /** @test */
    public function it_stores_avatar_and_removes_previous_from_disk()
    {
        $thread = Thread::factory()->group()->create(['image' => 'avatar.jpg']);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($thread->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);

        app(UpdateGroupAvatar::class)->execute($thread, [
            'image' => UploadedFile::fake()->image('picture.jpg'),
        ]);

        Storage::disk('messenger')->assertMissing($thread->getAvatarDirectory().'/avatar.jpg');
        Storage::disk('messenger')->assertExists($thread->getAvatarPath());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);
        $thread = Thread::factory()->group()->create(['image' => '5.png']);

        app(UpdateGroupAvatar::class)->execute($thread, [
            'default' => '1.png',
        ]);

        Event::assertDispatched(function (ThreadAvatarBroadcast $event) use ($thread) {
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());

            return true;
        });
        Event::assertDispatched(function (ThreadAvatarEvent $event) use ($thread) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);

            return true;
        });
    }

    /** @test */
    public function it_doesnt_fires_events_if_nothing_changed()
    {
        BaseMessengerAction::enableEvents();
        $thread = Thread::factory()->group()->create(['image' => '1.png']);

        $this->doesntExpectEvents([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        app(UpdateGroupAvatar::class)->execute($thread, [
            'default' => '1.png',
        ]);
    }

    /** @test */
    public function it_dispatches_listeners()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $thread = Thread::factory()->group()->create(['image' => '5.png']);

        app(UpdateGroupAvatar::class)->withoutBroadcast()->execute($thread, [
            'default' => '1.png',
        ]);

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === ThreadAvatarMessage::class;
        });
    }
}
