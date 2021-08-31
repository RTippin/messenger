<?php

namespace RTippin\Messenger\Tests\Actions;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\StoreGroupAvatar;
use RTippin\Messenger\Broadcasting\ThreadAvatarBroadcast;
use RTippin\Messenger\Events\ThreadAvatarEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\ThreadAvatarMessage;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\FileService;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreGroupAvatarTest extends FeatureTestCase
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
        Messenger::setThreadAvatars(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Group avatars are currently disabled.');

        app(StoreGroupAvatar::class)->execute(Thread::factory()->group()->create(), UploadedFile::fake()->image('picture.jpg'));
    }

    /** @test */
    public function it_throws_exception_if_transaction_fails_and_removes_uploaded_avatar()
    {
        $thread = Thread::factory()->group()->create(['image' => 'avatar.png']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Storage Error');
        $fileService = $this->mock(FileService::class);
        $fileService->shouldReceive([
            'setType' => Mockery::self(),
            'setDisk' => Mockery::self(),
            'setDirectory' => Mockery::self(),
            'upload' => 'avatar.jpg',
        ]);
        $fileService->shouldReceive('destroy')->andThrow(new Exception('Storage Error'));

        app(StoreGroupAvatar::class)->execute($thread, UploadedFile::fake()->image('picture.jpg'));
    }

    /** @test */
    public function it_updates_thread()
    {
        $thread = Thread::factory()->group()->create();

        app(StoreGroupAvatar::class)->execute($thread, UploadedFile::fake()->image('picture.jpg'));

        $this->assertNotNull($thread->image);
    }

    /** @test */
    public function it_stores_avatar_and_removes_previous_from_disk()
    {
        $thread = Thread::factory()->group()->create(['image' => 'avatar.jpg']);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($thread->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);

        app(StoreGroupAvatar::class)->execute($thread, UploadedFile::fake()->image('picture.jpg'));

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
        $thread = Thread::factory()->group()->create();

        app(StoreGroupAvatar::class)->execute($thread, UploadedFile::fake()->image('picture.jpg'));

        Event::assertDispatched(function (ThreadAvatarBroadcast $event) use ($thread) {
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());

            return true;
        });
        Event::assertDispatched(function (ThreadAvatarEvent $event) use ($thread) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);

            return true;
        });
        $this->logBroadcast(ThreadAvatarBroadcast::class, 'Uploading new avatar.');
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $thread = Thread::factory()->group()->create();

        app(StoreGroupAvatar::class)->withoutBroadcast()->execute($thread, UploadedFile::fake()->image('picture.jpg'));

        Bus::assertDispatched(ThreadAvatarMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('queued', false);
        $thread = Thread::factory()->group()->create();

        app(StoreGroupAvatar::class)->withoutBroadcast()->execute($thread, UploadedFile::fake()->image('picture.jpg'));

        Bus::assertDispatchedSync(ThreadAvatarMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('enabled', false);
        $thread = Thread::factory()->group()->create();

        app(StoreGroupAvatar::class)->withoutBroadcast()->execute($thread, UploadedFile::fake()->image('picture.jpg'));

        Bus::assertNotDispatched(ThreadAvatarMessage::class);
    }
}
