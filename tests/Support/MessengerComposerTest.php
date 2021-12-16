<?php

namespace RTippin\Messenger\Tests\Support;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Messages\AddReaction;
use RTippin\Messenger\Actions\Messages\StoreAudioMessage;
use RTippin\Messenger\Actions\Messages\StoreDocumentMessage;
use RTippin\Messenger\Actions\Messages\StoreImageMessage;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Actions\Messages\StoreVideoMessage;
use RTippin\Messenger\Actions\Threads\MarkParticipantRead;
use RTippin\Messenger\Actions\Threads\SendKnock;
use RTippin\Messenger\Actions\Threads\StorePrivateThread;
use RTippin\Messenger\Broadcasting\ClientEvents\Read;
use RTippin\Messenger\Broadcasting\ClientEvents\StopTyping;
use RTippin\Messenger\Broadcasting\ClientEvents\Typing;
use RTippin\Messenger\Broadcasting\KnockBroadcast;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Broadcasting\ParticipantReadBroadcast;
use RTippin\Messenger\Broadcasting\ReactionAddedBroadcast;
use RTippin\Messenger\Events\KnockEvent;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Events\NewThreadEvent;
use RTippin\Messenger\Events\ParticipantReadEvent;
use RTippin\Messenger\Events\ReactionAddedEvent;
use RTippin\Messenger\Exceptions\MessengerComposerException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\MessengerComposer;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\OtherModel;

class MessengerComposerTest extends FeatureTestCase
{
    private MessengerComposer $composer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->composer = app(MessengerComposer::class);
    }

    /** @test */
    public function messenger_composer_facade_resolves_new_instance_of_composer()
    {
        $this->assertInstanceOf(MessengerComposer::class, \RTippin\Messenger\Facades\MessengerComposer::getInstance());
        $this->assertNotSame($this->composer, \RTippin\Messenger\Facades\MessengerComposer::getInstance());
    }

    /** @test */
    public function messenger_composer_helper_resolves_new_instance_of_composer()
    {
        $this->assertInstanceOf(MessengerComposer::class, messengerComposer()->getInstance());
        $this->assertNotSame($this->composer, messengerComposer()->getInstance());
    }

    /** @test */
    public function it_throws_exception_when_to_invalid()
    {
        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('Invalid "TO" entity. Thread or messenger provider must be used.');

        $this->composer->to(new OtherModel);
    }

    /** @test */
    public function it_throws_exception_when_from_invalid()
    {
        Messenger::registerProviders([], true);
        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('Invalid "FROM" entity. Messenger provider must be supplied.');

        $this->composer->from($this->tippin);
    }

    /** @test */
    public function it_throws_exception_when_composing_without_to_set()
    {
        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('No "TO" entity has been set.');

        $this->composer->message('Test');
    }

    /** @test */
    public function it_throws_exception_when_composing_without_from_set()
    {
        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('No "FROM" provider has been set.');

        $this->composer->to($this->doe)->message('Test');
    }

    /** @test */
    public function it_throws_exception_when_storing_new_private_thread_fails()
    {
        $this->mock(StorePrivateThread::class)
            ->shouldReceive('execute')
            ->andThrow(new Exception('New Private Failed'));

        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('New Private Failed');

        $this->composer
            ->to($this->doe)
            ->from($this->tippin)
            ->message('Test');
    }

    /** @test */
    public function it_uses_set_provider_without_from()
    {
        Messenger::setProvider($this->tippin);

        $this->composer->to($this->doe)->message('Test');

        $this->assertFalse(Messenger::isScopedProviderSet());
        $this->assertSame($this->tippin, Messenger::getProvider());
        $this->assertDatabaseHas('messages', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'body' => 'Test',
        ]);
    }

    /** @test */
    public function it_uses_from_to_set_scoped_provider()
    {
        $this->composer->from($this->doe);

        $this->assertTrue(Messenger::isScopedProviderSet());
        $this->assertSame($this->doe, Messenger::getProvider());
    }

    /** @test */
    public function it_creates_new_pending_thread_if_not_friends()
    {
        $this->composer
            ->to($this->doe)
            ->from($this->tippin)
            ->message('Test');

        $this->assertDatabaseCount('threads', 1);
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
    public function it_creates_new_non_pending_thread_if_friends()
    {
        $this->createFriends($this->tippin, $this->doe);

        $this->composer
            ->to($this->doe)
            ->from($this->tippin)
            ->message('Test');

        $this->assertDatabaseCount('threads', 1);
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
    public function it_created_new_thread_firing_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $this->composer
            ->to($this->doe)
            ->from($this->tippin)
            ->message('Test');

        Event::assertDispatched(NewThreadBroadcast::class);
        Event::assertDispatched(NewThreadEvent::class);
    }

    /** @test */
    public function it_creates_new_thread_without_broadcast()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $this->composer
            ->to($this->doe)
            ->from($this->tippin)
            ->silent()
            ->message('Test');

        Event::assertNotDispatched(NewThreadBroadcast::class);
        Event::assertDispatched(NewThreadEvent::class);
    }

    /** @test */
    public function it_creates_new_thread_without_broadcast_and_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $this->composer
            ->to($this->doe)
            ->from($this->tippin)
            ->silent(true)
            ->message('Test');

        Event::assertNotDispatched(NewThreadBroadcast::class);
        Event::assertNotDispatched(NewThreadEvent::class);
    }

    /** @test */
    public function it_sends_message_with_existing_thread()
    {
        $thread = Thread::create();
        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->message('Test');

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'body' => 'Test',
            'type' => 0,
        ]);
    }

    /** @test */
    public function it_sends_message_with_null_body()
    {
        $thread = Thread::create();
        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->message(null);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'body' => null,
        ]);
    }

    /** @test */
    public function it_sends_message_with_extra()
    {
        $thread = Thread::create();
        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->message('Test', null, ['extra' => true]);

        $this->assertDatabaseHas('messages', [
            'body' => 'Test',
            'extra' => '{"extra":true}',
            'thread_id' => $thread->id,
        ]);
    }

    /** @test */
    public function it_sends_message_and_returns_message_action()
    {
        $message = $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->message('Test');

        $this->assertInstanceOf(StoreMessage::class, $message);
    }

    /** @test */
    public function it_sends_message_and_creates_new_thread()
    {
        $this->composer
            ->to($this->doe)
            ->from($this->tippin)
            ->message('Test');

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_sends_message_and_flushes_state()
    {
        //Set our thread and user. Sending the message should flush our states.
        $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->message('Test');

        $this->assertNull(Messenger::getProvider());
        $this->assertFalse(Messenger::isProviderSet());

        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('No "TO" entity has been set.');
        //TO and FROM have been reset, thus we expect an exception calling to
        //the method on the same instance without setting a new TO and FROM.
        $this->composer->message('Test');
    }

    /** @test */
    public function it_sends_message_and_flushes_state_reverting_prior_provider()
    {
        //Set our main provider to doe.
        Messenger::setProvider($this->doe);
        //Our scoped provider tippin will be set for the message action.
        //When complete, doe should be reverted to the active provider.
        $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->message('Test');

        $this->assertSame($this->doe, Messenger::getProvider());
        $this->assertFalse(Messenger::isScopedProviderSet());
    }

    /** @test */
    public function it_sends_message_with_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->message('Test');

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_message_without_broadcast()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->silent()
            ->message('Test');

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_message_without_broadcast_and_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->silent(true)
            ->message('Test');

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertNotDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_image_message_with_existing_thread()
    {
        $thread = Thread::create();

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->image(UploadedFile::fake()->image('test.jpg'));

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'type' => 1,
        ]);
    }

    /** @test */
    public function it_sends_image_message_and_returns_image_action()
    {
        $image = $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->image(UploadedFile::fake()->image('test.jpg'));

        $this->assertInstanceOf(StoreImageMessage::class, $image);
    }

    /** @test */
    public function it_sends_image_message_and_creates_new_thread()
    {
        $this->composer
            ->to($this->doe)
            ->from($this->tippin)
            ->image(UploadedFile::fake()->image('test.jpg'));

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_sends_image_message_and_flushes_state()
    {
        //Set our thread and user. Sending the image should flush our states.
        $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->image(UploadedFile::fake()->image('test.jpg'));

        $this->assertNull(Messenger::getProvider());
        $this->assertFalse(Messenger::isProviderSet());

        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('No "TO" entity has been set.');
        //TO and FROM have been reset, thus we expect an exception calling to
        //the method on the same instance without setting a new TO and FROM.
        $this->composer->image(UploadedFile::fake()->image('test.jpg'));
    }

    /** @test */
    public function it_sends_image_message_and_flushes_state_reverting_prior_provider()
    {
        //Set our main provider to doe.
        Messenger::setProvider($this->doe);
        //Our scoped provider tippin will be set for the image action.
        //When complete, doe should be reverted to the active provider.
        $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->image(UploadedFile::fake()->image('test.jpg'));

        $this->assertSame($this->doe, Messenger::getProvider());
        $this->assertFalse(Messenger::isScopedProviderSet());
    }

    /** @test */
    public function it_sends_image_message_with_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->image(UploadedFile::fake()->image('test.jpg'));

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_image_message_without_broadcast()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->silent()
            ->image(UploadedFile::fake()->image('test.jpg'));

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_image_message_without_broadcast_and_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->silent(true)
            ->image(UploadedFile::fake()->image('test.jpg'));

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertNotDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_document_message_with_existing_thread()
    {
        $thread = Thread::create();
        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->document(UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'));

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'type' => 2,
        ]);
    }

    /** @test */
    public function it_sends_document_message_and_returns_document_action()
    {
        $document = $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->document(UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'));

        $this->assertInstanceOf(StoreDocumentMessage::class, $document);
    }

    /** @test */
    public function it_sends_document_message_and_creates_new_thread()
    {
        $this->composer
            ->to($this->doe)
            ->from($this->tippin)
            ->document(UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'));

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_sends_document_message_and_flushes_state()
    {
        //Set our thread and user. Sending the document should flush our states.
        $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->document(UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'));

        $this->assertNull(Messenger::getProvider());
        $this->assertFalse(Messenger::isProviderSet());

        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('No "TO" entity has been set.');
        //TO and FROM have been reset, thus we expect an exception calling to
        //the method on the same instance without setting a new TO and FROM.
        $this->composer->document(UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'));
    }

    /** @test */
    public function it_sends_document_message_and_flushes_state_reverting_prior_provider()
    {
        //Set our main provider to doe.
        Messenger::setProvider($this->doe);
        //Our scoped provider tippin will be set for the document action.
        //When complete, doe should be reverted to the active provider.
        $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->document(UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'));

        $this->assertSame($this->doe, Messenger::getProvider());
        $this->assertFalse(Messenger::isScopedProviderSet());
    }

    /** @test */
    public function it_sends_document_message_with_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->document(UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'));

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_document_message_without_broadcast()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->silent()
            ->document(UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'));

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_document_message_without_broadcast_and_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->silent(true)
            ->document(UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'));

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertNotDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_audio_message_with_existing_thread()
    {
        $thread = Thread::create();
        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->audio(UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'));

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'type' => 3,
        ]);
    }

    /** @test */
    public function it_sends_audio_message_and_returns_audio_action()
    {
        $audio = $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->audio(UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'));

        $this->assertInstanceOf(StoreAudioMessage::class, $audio);
    }

    /** @test */
    public function it_sends_audio_message_and_creates_new_thread()
    {
        $this->composer
            ->to($this->doe)
            ->from($this->tippin)
            ->audio(UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'));

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_sends_audio_message_and_flushes_state()
    {
        //Set our thread and user. Sending the audio should flush our states.
        $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->audio(UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'));

        $this->assertNull(Messenger::getProvider());
        $this->assertFalse(Messenger::isProviderSet());

        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('No "TO" entity has been set.');
        //TO and FROM have been reset, thus we expect an exception calling to
        //the method on the same instance without setting a new TO and FROM.
        $this->composer->audio(UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'));
    }

    /** @test */
    public function it_sends_audio_message_and_flushes_state_reverting_prior_provider()
    {
        //Set our main provider to doe.
        Messenger::setProvider($this->doe);
        //Our scoped provider tippin will be set for the audio action.
        //When complete, doe should be reverted to the active provider.
        $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->audio(UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'));

        $this->assertSame($this->doe, Messenger::getProvider());
        $this->assertFalse(Messenger::isScopedProviderSet());
    }

    /** @test */
    public function it_sends_audio_message_with_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->audio(UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'));

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_audio_message_without_broadcast()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->silent()
            ->audio(UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'));

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_audio_message_without_broadcast_and_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->silent(true)
            ->audio(UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'));

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertNotDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_video_message_with_existing_thread()
    {
        $thread = Thread::create();
        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->video(UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'));

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'type' => Message::VIDEO_MESSAGE,
        ]);
    }

    /** @test */
    public function it_sends_video_message_and_returns_video_action()
    {
        $video = $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->video(UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'));

        $this->assertInstanceOf(StoreVideoMessage::class, $video);
    }

    /** @test */
    public function it_sends_video_message_and_creates_new_thread()
    {
        $this->composer
            ->to($this->doe)
            ->from($this->tippin)
            ->video(UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'));

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_sends_video_message_and_flushes_state()
    {
        //Set our thread and user. Sending the video should flush our states.
        $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->video(UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'));

        $this->assertNull(Messenger::getProvider());
        $this->assertFalse(Messenger::isProviderSet());

        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('No "TO" entity has been set.');
        //TO and FROM have been reset, thus we expect an exception calling to
        //the method on the same instance without setting a new TO and FROM.
        $this->composer->video(UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'));
    }

    /** @test */
    public function it_sends_video_message_and_flushes_state_reverting_prior_provider()
    {
        //Set our main provider to doe.
        Messenger::setProvider($this->doe);
        //Our scoped provider tippin will be set for the video action.
        //When complete, doe should be reverted to the active provider.
        $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->video(UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'));

        $this->assertSame($this->doe, Messenger::getProvider());
        $this->assertFalse(Messenger::isScopedProviderSet());
    }

    /** @test */
    public function it_sends_video_message_with_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->video(UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'));

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_video_message_without_broadcast()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->silent()
            ->video(UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'));

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_video_message_without_broadcast_and_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->silent(true)
            ->video(UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'));

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertNotDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_adds_reaction()
    {
        $thread = Thread::create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->reaction($message, ':joy:');

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $message->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'reaction' => ':joy:',
        ]);
    }

    /** @test */
    public function it_adds_reaction_and_returns_reaction_action()
    {
        $thread = Thread::create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $reaction = $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->reaction($message, ':joy:');

        $this->assertInstanceOf(AddReaction::class, $reaction);
    }

    /** @test */
    public function it_adds_reaction_and_flushes_state()
    {
        $thread = Thread::create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        //Set our thread and user. Sending the reaction should flush our states.
        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->reaction($message, ':joy:');

        $this->assertNull(Messenger::getProvider());
        $this->assertFalse(Messenger::isProviderSet());

        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('No "TO" entity has been set.');
        //TO and FROM have been reset, thus we expect an exception calling to
        //the method on the same instance without setting a new TO and FROM.
        $this->composer->reaction($message, ':joy:');
    }

    /** @test */
    public function it_adds_reaction_and_flushes_state_reverting_prior_provider()
    {
        $thread = Thread::create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        //Set our main provider to doe.
        Messenger::setProvider($this->doe);
        //Our scoped provider tippin will be set for the reaction action.
        //When complete, doe should be reverted to the active provider.
        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->reaction($message, ':joy:');

        $this->assertSame($this->doe, Messenger::getProvider());
        $this->assertFalse(Messenger::isScopedProviderSet());
    }

    /** @test */
    public function it_adds_reaction_with_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ReactionAddedBroadcast::class,
            ReactionAddedEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->reaction($message, ':joy:');

        Event::assertDispatched(ReactionAddedBroadcast::class);
        Event::assertDispatched(ReactionAddedEvent::class);
    }

    /** @test */
    public function it_adds_reaction_without_broadcast()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ReactionAddedBroadcast::class,
            ReactionAddedEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->silent()
            ->reaction($message, ':joy:');

        Event::assertNotDispatched(ReactionAddedBroadcast::class);
        Event::assertDispatched(ReactionAddedEvent::class);
    }

    /** @test */
    public function it_adds_reaction_without_broadcast_and_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ReactionAddedBroadcast::class,
            ReactionAddedEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->silent(true)
            ->reaction($message, ':joy:');

        Event::assertNotDispatched(ReactionAddedBroadcast::class);
        Event::assertNotDispatched(ReactionAddedEvent::class);
    }

    /** @test */
    public function it_sends_knock_with_existing_thread()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            KnockBroadcast::class,
            KnockEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->knock();

        Event::assertDispatched(KnockBroadcast::class);
        Event::assertDispatched(KnockEvent::class);
    }

    /** @test */
    public function it_sends_knock_and_returns_knock_action()
    {
        $knock = $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->knock();

        $this->assertInstanceOf(SendKnock::class, $knock);
    }

    /** @test */
    public function it_sends_knock_and_creates_new_thread()
    {
        $this->composer
            ->to($this->doe)
            ->from($this->tippin)
            ->knock();

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
    }

    /** @test */
    public function it_sends_knock_and_flushes_state()
    {
        //Set our thread and user. Sending the knock should flush our states.
        $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->knock();

        $this->assertNull(Messenger::getProvider());
        $this->assertFalse(Messenger::isProviderSet());

        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('No "TO" entity has been set.');
        //TO and FROM have been reset, thus we expect an exception calling to
        //the method on the same instance without setting a new TO and FROM.
        $this->composer->knock();
    }

    /** @test */
    public function it_sends_knock_and_flushes_state_reverting_prior_provider()
    {
        //Set our main provider to doe.
        Messenger::setProvider($this->doe);
        //Our scoped provider tippin will be set for the knock action.
        //When complete, doe should be reverted to the active provider.
        $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->knock();

        $this->assertSame($this->doe, Messenger::getProvider());
        $this->assertFalse(Messenger::isScopedProviderSet());
    }

    /** @test */
    public function it_marks_read_with_existing_thread()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ParticipantReadBroadcast::class,
            ParticipantReadEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->read();

        Event::assertDispatched(ParticipantReadBroadcast::class);
        Event::assertDispatched(ParticipantReadEvent::class);
    }

    /** @test */
    public function it_marks_read_using_supplied_participant()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ParticipantReadBroadcast::class,
            ParticipantReadEvent::class,
        ]);
        $participant = Participant::factory()->for(Thread::create())->owner($this->tippin)->create();

        $this->composer->read($participant);

        Event::assertDispatched(ParticipantReadBroadcast::class);
        Event::assertDispatched(ParticipantReadEvent::class);
    }

    /** @test */
    public function it_marks_read_without_broadcast()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ParticipantReadBroadcast::class,
            ParticipantReadEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->silent()
            ->read();

        Event::assertNotDispatched(ParticipantReadBroadcast::class);
        Event::assertDispatched(ParticipantReadEvent::class);
    }

    /** @test */
    public function it_marks_read_without_broadcast_and_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ParticipantReadBroadcast::class,
            ParticipantReadEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->silent(true)
            ->read();

        Event::assertNotDispatched(ParticipantReadBroadcast::class);
        Event::assertNotDispatched(ParticipantReadEvent::class);
    }

    /** @test */
    public function it_marks_read_and_returns_read_action()
    {
        $read = $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->read();

        $this->assertInstanceOf(MarkParticipantRead::class, $read);
    }

    /** @test */
    public function it_marks_read_and_creates_new_thread()
    {
        $this->composer
            ->to($this->doe)
            ->from($this->tippin)
            ->read();

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
    }

    /** @test */
    public function it_marks_read_and_flushes_state()
    {
        //Set our thread and user. Sending the read should flush our states.
        $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->read();

        $this->assertNull(Messenger::getProvider());
        $this->assertFalse(Messenger::isProviderSet());

        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('No "TO" entity has been set.');
        //TO and FROM have been reset, thus we expect an exception calling to
        //the method on the same instance without setting a new TO and FROM.
        $this->composer->read();
    }

    /** @test */
    public function it_marks_read_and_flushes_state_reverting_prior_provider()
    {
        //Set our main provider to doe.
        Messenger::setProvider($this->doe);
        //Our scoped provider tippin will be set for the read action.
        //When complete, doe should be reverted to the active provider.
        $this->composer
            ->to(Thread::create())
            ->from($this->tippin)
            ->read();

        $this->assertSame($this->doe, Messenger::getProvider());
        $this->assertFalse(Messenger::isScopedProviderSet());
    }

    /** @test */
    public function it_sends_typing_broadcast()
    {
        $thread = Thread::create();
        Event::fake(Typing::class);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->emitTyping();

        Event::assertDispatched(function (Typing $event) use ($thread) {
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());
            $this->assertSame($this->tippin->getKey(), $event->broadcastWith()['provider_id']);

            return true;
        });
    }

    /** @test */
    public function it_sends_stopped_typing_broadcast()
    {
        $thread = Thread::create();
        Event::fake(StopTyping::class);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->emitStopTyping();

        Event::assertDispatched(function (StopTyping $event) use ($thread) {
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());
            $this->assertSame($this->tippin->getKey(), $event->broadcastWith()['provider_id']);

            return true;
        });
    }

    /** @test */
    public function it_sends_read_broadcast()
    {
        $thread = Thread::create();
        Event::fake(Read::class);

        $this->composer
            ->to($thread)
            ->from($this->tippin)
            ->emitRead();

        Event::assertDispatched(function (Read $event) use ($thread) {
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());
            $this->assertSame($this->tippin->getKey(), $event->broadcastWith()['provider_id']);

            return true;
        });
    }
}
