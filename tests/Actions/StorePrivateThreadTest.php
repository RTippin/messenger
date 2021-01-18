<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Threads\StorePrivateThread;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Events\NewThreadEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Tests\FeatureTestCase;

class StorePrivateThreadTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        Storage::fake(Messenger::getThreadStorage('disk'));

        Messenger::setProvider($this->tippin);
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $config = $app->get('config');

        $config->set('messenger.providers.user.provider_interactions.can_message', false);
    }

    /** @test */
    public function store_private_throws_exception_if_one_already_found_between_providers()
    {
        $this->expectException(AuthorizationException::class);

        $this->createPrivateThread($this->tippin, $this->doe);

        app(StorePrivateThread::class)->withoutDispatches()->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseCount('threads', 1);
    }

    /** @test */
    public function store_private_throws_exception_if_provider_interactions_denies_messaging_first()
    {
        $this->expectException(AuthorizationException::class);

        app(StorePrivateThread::class)->withoutDispatches()->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'company',
            'recipient_id' => $this->companyDevelopers()->getKey(),
        ]);

        $this->assertDatabaseCount('threads', 0);
    }

    /** @test */
    public function store_private_stores_thread_participants_and_message()
    {
        app(StorePrivateThread::class)->withoutDispatches()->execute([
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
    public function store_private_marks_recipient_pending_when_not_friends()
    {
        app(StorePrivateThread::class)->withoutDispatches()->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'pending' => false,
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => get_class($this->doe),
            'pending' => true,
        ]);
    }

    /** @test */
    public function store_private_not_pending_when_providers_are_friends()
    {
        $this->createFriends($this->tippin, $this->doe);

        app(StorePrivateThread::class)->withoutDispatches()->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'pending' => false,
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => get_class($this->doe),
            'pending' => false,
        ]);
    }

    /** @test */
    public function store_private_pending_fires_events()
    {
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
            $this->assertContains('private-user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertTrue($event->broadcastWith()['thread']['pending']);

            return true;
        });

        Event::assertDispatched(function (NewThreadEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame(1, $event->thread->type);

            return true;
        });
    }

    /** @test */
    public function store_private_not_pending_fires_events()
    {
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
    }

    /** @test */
    public function store_private_doesnt_expect_message_events()
    {
        $this->doesntExpectEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        app(StorePrivateThread::class)->execute([
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);
    }

    /** @test */
    public function store_private_stores_image_message()
    {
        app(StorePrivateThread::class)->withoutDispatches()->execute([
            'image' => UploadedFile::fake()->image('picture.jpg'),
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseHas('messages', [
            'type' => 1,
        ]);

        Storage::disk(Messenger::getThreadStorage('disk'))->assertExists(Message::image()->first()->getImagePath());
    }

    /** @test */
    public function store_private_stores_document_message()
    {
        app(StorePrivateThread::class)->withoutDispatches()->execute([
            'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ]);

        $this->assertDatabaseHas('messages', [
            'type' => 2,
        ]);

        Storage::disk(Messenger::getThreadStorage('disk'))->assertExists(Message::document()->first()->getDocumentPath());
    }
}
