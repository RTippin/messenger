<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\StorePrivateThread;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Events\NewThreadEvent;
use RTippin\Messenger\Exceptions\NewThreadException;
use RTippin\Messenger\Exceptions\ProviderNotFoundException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;

class StorePrivateThreadTest extends FeatureTestCase
{
    use BroadcastLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_provider_not_found()
    {
        $this->expectException(ProviderNotFoundException::class);
        $this->expectExceptionMessage('We were unable to locate the recipient you requested.');

        app(StorePrivateThread::class)->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => 404,
        ]);
    }

    /** @test */
    public function it_throws_exception_if_one_exist_between_providers()
    {
        $this->createPrivateThread($this->tippin, $this->doe);

        $this->expectException(NewThreadException::class);
        $this->expectExceptionMessage('You already have an existing conversation with John Doe.');

        app(StorePrivateThread::class)->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);
    }

    /** @test */
    public function it_doesnt_check_if_thread_exist_between_providers_if_using_recipient_override()
    {
        $this->createPrivateThread($this->tippin, $this->doe);

        app(StorePrivateThread::class)->execute([
            'message' => 'Hello World!',
        ], $this->doe);

        $this->assertDatabaseCount('threads', 2);
    }

    /** @test */
    public function it_throws_exception_if_provider_interactions_denies_messaging_first()
    {
        UserModel::$cantMessage = [CompanyModel::class];
        Messenger::registerProviders([UserModel::class, CompanyModel::class]);
        Messenger::setProvider($this->tippin);

        $this->expectException(NewThreadException::class);
        $this->expectExceptionMessage('Not authorized to start conversations with Developers.');

        app(StorePrivateThread::class)->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'company',
            'recipient_id' => $this->developers->getKey(),
        ]);
    }

    /** @test */
    public function it_stores_thread_with_participants_and_message()
    {
        app(StorePrivateThread::class)->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseCount('messages', 1);
        $this->assertDatabaseHas('messages', [
            'type' => 0,
            'body' => 'Hello World!',
        ]);
    }

    /** @test */
    public function it_stores_thread_with_participants_and_no_message()
    {
        app(StorePrivateThread::class)->execute([
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseCount('messages', 0);
    }

    /** @test */
    public function it_marks_recipient_pending_if_not_friends()
    {
        app(StorePrivateThread::class)->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'pending' => false,
        ]);
        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'pending' => true,
        ]);
    }

    /** @test */
    public function it_marks_recipient_pending_if_provider_not_friendable()
    {
        UserModel::$friendable = false;
        Messenger::registerProviders([UserModel::class]);
        Messenger::setProvider($this->tippin);
        $this->createFriends($this->tippin, $this->doe);

        app(StorePrivateThread::class)->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'pending' => false,
        ]);
        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'pending' => true,
        ]);
    }

    /** @test */
    public function it_doesnt_mark_recipient_pending_if_verify_friendships_disabled()
    {
        Messenger::setVerifyPrivateThreadFriendship(false);

        app(StorePrivateThread::class)->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'pending' => false,
        ]);
        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'pending' => false,
        ]);
    }

    /** @test */
    public function it_is_not_pending_if_providers_are_friends()
    {
        $this->createFriends($this->tippin, $this->doe);

        app(StorePrivateThread::class)->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'pending' => false,
        ]);
        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'pending' => false,
        ]);
    }

    /** @test */
    public function it_fires_pending_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        app(StorePrivateThread::class)->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        Event::assertDispatched(function (NewThreadBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertTrue($event->broadcastWith()['thread']['pending']);

            return true;
        });
        Event::assertDispatched(function (NewThreadEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame(1, $event->thread->type);

            return true;
        });
        $this->logBroadcast(NewThreadBroadcast::class, 'New pending private thread.');
    }

    /** @test */
    public function it_fires_not_pending_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);
        $this->createFriends($this->tippin, $this->doe);

        app(StorePrivateThread::class)->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        Event::assertDispatched(function (NewThreadBroadcast $event) {
            return $event->broadcastWith()['thread']['pending'] === false;
        });
        Event::assertDispatched(NewThreadEvent::class);
        $this->logBroadcast(NewThreadBroadcast::class, 'New private thread.');
    }

    /** @test */
    public function it_doesnt_fire_message_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        app(StorePrivateThread::class)->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertNotDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_stores_image_message()
    {
        app(StorePrivateThread::class)->execute([
            'image' => UploadedFile::fake()->image('picture.jpg'),
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseHas('messages', [
            'type' => 1,
        ]);
        Storage::disk('messenger')->assertExists(Message::image()->first()->getImagePath());
    }

    /** @test */
    public function it_stores_document_message()
    {
        app(StorePrivateThread::class)->execute([
            'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseHas('messages', [
            'type' => 2,
        ]);
        Storage::disk('messenger')->assertExists(Message::document()->first()->getDocumentPath());
    }

    /** @test */
    public function it_stores_audio_message()
    {
        app(StorePrivateThread::class)->execute([
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseHas('messages', [
            'type' => 3,
        ]);
        Storage::disk('messenger')->assertExists(Message::audio()->first()->getAudioPath());
    }

    /** @test */
    public function it_stores_video_message()
    {
        app(StorePrivateThread::class)->execute([
            'video' => UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'),
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseHas('messages', [
            'type' => Message::VIDEO_MESSAGE,
        ]);
        Storage::disk('messenger')->assertExists(Message::video()->first()->getVideoPath());
    }

    /** @test */
    public function it_can_add_extra_data_on_message()
    {
        app(StorePrivateThread::class)->execute([
            'message' => 'Extra',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
            'extra' => ['test' => true],
        ]);

        $this->assertDatabaseHas('messages', [
            'type' => 0,
            'extra' => '{"test":true}',
        ]);
    }
}
