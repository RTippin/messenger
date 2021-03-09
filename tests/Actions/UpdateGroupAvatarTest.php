<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Threads\UpdateGroupAvatar;
use RTippin\Messenger\Broadcasting\ThreadAvatarBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ThreadAvatarEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\ThreadAvatarMessage;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateGroupAvatarTest extends FeatureTestCase
{
    private Thread $group;
    private MessengerProvider $tippin;
    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->group = $this->createGroupThread($this->tippin);
        Messenger::setProvider($this->tippin);
        $this->disk = Messenger::getThreadStorage('disk');
        Storage::fake($this->disk);
        $this->group->update([
            'image' => 'avatar.jpg',
        ]);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($this->group->getStorageDirectory().'/avatar', 'avatar.jpg', [
            'disk' => $this->disk,
        ]);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setThreadAvatarUpload(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Group avatar uploads are currently disabled.');

        app(UpdateGroupAvatar::class)->withoutDispatches()->execute(
            $this->group,
            [
                'image' => UploadedFile::fake()->image('picture.jpg'),
            ]
        );
    }

    /** @test */
    public function it_updates_default_thread_avatar()
    {
        app(UpdateGroupAvatar::class)->withoutDispatches()->execute(
            $this->group,
            [
                'default' => '1.png',
            ]
        );

        $this->assertDatabaseHas('threads', [
            'type' => 2,
            'image' => '1.png',
        ]);
    }

    /** @test */
    public function it_updates_default_and_removes_existing_avatar_from_disk()
    {
        app(UpdateGroupAvatar::class)->withoutDispatches()->execute(
            $this->group,
            [
                'default' => '3.png',
            ]
        );

        $this->assertDatabaseHas('threads', [
            'type' => 2,
            'image' => '3.png',
        ]);
        Storage::disk($this->disk)->assertMissing($this->group->getStorageDirectory().'/avatar/avatar.jpg');
    }

    /** @test */
    public function it_stores_avatar_and_removes_previous_from_disk()
    {
        app(UpdateGroupAvatar::class)->withoutDispatches()->execute(
            $this->group,
            [
                'image' => UploadedFile::fake()->image('picture.jpg'),
            ]
        );

        Storage::disk($this->disk)->assertMissing($this->group->getStorageDirectory().'/avatar/avatar.jpg');
        Storage::disk($this->disk)->assertExists($this->group->getAvatarPath());
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        app(UpdateGroupAvatar::class)->execute(
            $this->group,
            [
                'default' => '1.png',
            ]
        );

        Event::assertDispatched(function (ThreadAvatarBroadcast $event) {
            $this->assertContains('First Test Group', $event->broadcastWith());
            $this->assertContains('presence-messenger.thread.'.$this->group->id, $event->broadcastOn());

            return true;
        });
        Event::assertDispatched(function (ThreadAvatarEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->group->id, $event->thread->id);

            return true;
        });
    }

    /** @test */
    public function it_dispatches_listeners()
    {
        Bus::fake();

        app(UpdateGroupAvatar::class)->withoutBroadcast()->execute(
            $this->group,
            [
                'default' => '1.png',
            ]
        );

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === ThreadAvatarMessage::class;
        });
    }
}
