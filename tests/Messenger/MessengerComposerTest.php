<?php

namespace RTippin\Messenger\Tests\Messenger;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Messages\AddReaction;
use RTippin\Messenger\Actions\Messages\StoreAudioMessage;
use RTippin\Messenger\Actions\Messages\StoreDocumentMessage;
use RTippin\Messenger\Actions\Messages\StoreImageMessage;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Actions\Threads\MarkParticipantRead;
use RTippin\Messenger\Actions\Threads\SendKnock;
use RTippin\Messenger\Broadcasting\ClientEvents\Read;
use RTippin\Messenger\Broadcasting\ClientEvents\StopTyping;
use RTippin\Messenger\Broadcasting\ClientEvents\Typing;
use RTippin\Messenger\Broadcasting\KnockBroadcast;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Broadcasting\ParticipantReadBroadcast;
use RTippin\Messenger\Broadcasting\ReactionAddedBroadcast;
use RTippin\Messenger\Events\KnockEvent;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Events\ParticipantReadEvent;
use RTippin\Messenger\Events\ReactionAddedEvent;
use RTippin\Messenger\Exceptions\MessengerComposerException;
use RTippin\Messenger\Models\Message;
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
    public function messenger_composer_facade_resolves_to_composer()
    {
        $this->assertInstanceOf(MessengerComposer::class, \RTippin\Messenger\Facades\MessengerComposer::getInstance());
        $this->assertNotSame($this->composer, \RTippin\Messenger\Facades\MessengerComposer::getInstance());
    }

    /** @test */
    public function it_throws_exception_when_to_invalid()
    {
        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('Invalid "TO" entity. Thread or messenger provider must be used.');

        $this->composer->to(new OtherModel());
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
        DB::shouldReceive('transaction')->andThrow(new Exception('DB Error'));

        $this->expectException(MessengerComposerException::class);
        $this->expectExceptionMessage('Storing new private failed with the message: DB Error');

        app(MessengerComposer::class)->to($this->doe)->from($this->tippin)->message('Test');

        $this->assertDatabaseCount('threads', 0);
        $this->assertDatabaseCount('participants', 0);
        $this->assertDatabaseCount('messages', 0);
    }

    /** @test */
    public function it_sends_message_with_existing_thread()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->composer->to($thread)->from($this->tippin)->message('Test');

        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_sends_message_and_returns_message_action()
    {
        $thread = $this->createGroupThread($this->tippin);

        $message = $this->composer->to($thread)->from($this->tippin)->message('Test');

        $this->assertInstanceOf(StoreMessage::class, $message);
    }

    /** @test */
    public function it_sends_message_and_creates_new_thread()
    {
        $this->composer->to($this->doe)->from($this->tippin)->message('Test');

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_sends_message_with_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->composer->to($thread)->from($this->tippin)->message('Test');

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
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->composer->to($thread)->from($this->tippin)->silent()->message('Test');

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_image_message_with_existing_thread()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->composer->to($thread)->from($this->tippin)->image(UploadedFile::fake()->image('test.jpg'));

        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_sends_image_message_and_returns_image_action()
    {
        $thread = $this->createGroupThread($this->tippin);

        $image = $this->composer->to($thread)->from($this->tippin)->image(UploadedFile::fake()->image('test.jpg'));

        $this->assertInstanceOf(StoreImageMessage::class, $image);
    }

    /** @test */
    public function it_sends_image_message_and_creates_new_thread()
    {
        $this->composer->to($this->doe)->from($this->tippin)->image(UploadedFile::fake()->image('test.jpg'));

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_sends_image_message_with_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->composer->to($thread)->from($this->tippin)->image(UploadedFile::fake()->image('test.jpg'));

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
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->composer->to($thread)->from($this->tippin)->silent()->image(UploadedFile::fake()->image('test.jpg'));

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_document_message_with_existing_thread()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->composer->to($thread)->from($this->tippin)->document(UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'));

        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_sends_document_message_and_returns_document_action()
    {
        $thread = $this->createGroupThread($this->tippin);

        $document = $this->composer->to($thread)->from($this->tippin)->document(UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'));

        $this->assertInstanceOf(StoreDocumentMessage::class, $document);
    }

    /** @test */
    public function it_sends_document_message_and_creates_new_thread()
    {
        $this->composer->to($this->doe)->from($this->tippin)->document(UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'));

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_sends_document_message_with_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->composer->to($thread)->from($this->tippin)->document(UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'));

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
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->composer->to($thread)->from($this->tippin)->silent()->document(UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'));

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_sends_audio_message_with_existing_thread()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->composer->to($thread)->from($this->tippin)->audio(UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'));

        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_sends_audio_message_and_returns_audio_action()
    {
        $thread = $this->createGroupThread($this->tippin);

        $audio = $this->composer->to($thread)->from($this->tippin)->audio(UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'));

        $this->assertInstanceOf(StoreAudioMessage::class, $audio);
    }

    /** @test */
    public function it_sends_audio_message_and_creates_new_thread()
    {
        $this->composer->to($this->doe)->from($this->tippin)->audio(UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'));

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_sends_audio_message_with_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->composer->to($thread)->from($this->tippin)->audio(UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'));

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
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->composer->to($thread)->from($this->tippin)->silent()->audio(UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'));

        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_adds_reaction()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $this->composer->to($thread)->from($this->tippin)->reaction($message, ':joy:');

        $this->assertDatabaseCount('message_reactions', 1);
    }

    /** @test */
    public function it_adds_reaction_and_returns_reaction_action()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $reaction = $this->composer->to($thread)->from($this->tippin)->reaction($message, ':joy:');

        $this->assertInstanceOf(AddReaction::class, $reaction);
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

        $this->composer->to($thread)->from($this->tippin)->reaction($message, ':joy:');

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

        $this->composer->to($thread)->from($this->tippin)->silent()->reaction($message, ':joy:');

        Event::assertNotDispatched(ReactionAddedBroadcast::class);
        Event::assertDispatched(ReactionAddedEvent::class);
    }

    /** @test */
    public function it_sends_knock_with_existing_thread()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            KnockBroadcast::class,
            KnockEvent::class,
        ]);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->composer->to($thread)->from($this->tippin)->knock();

        Event::assertDispatched(KnockBroadcast::class);
        Event::assertDispatched(KnockEvent::class);
    }

    /** @test */
    public function it_sends_knock_and_returns_knock_action()
    {
        $thread = $this->createGroupThread($this->tippin);

        $knock = $this->composer->to($thread)->from($this->tippin)->knock();

        $this->assertInstanceOf(SendKnock::class, $knock);
    }

    /** @test */
    public function it_sends_knock_and_creates_new_thread()
    {
        $this->composer->to($this->doe)->from($this->tippin)->knock();

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
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

        $this->composer->to($thread)->from($this->tippin)->read();

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
        $thread = $this->createGroupThread($this->tippin, $this->doe);

        $this->composer->read($thread->participants()->first());

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

        $this->composer->to($thread)->from($this->tippin)->silent()->read();

        Event::assertNotDispatched(ParticipantReadBroadcast::class);
        Event::assertDispatched(ParticipantReadEvent::class);
    }

    /** @test */
    public function it_marks_read_and_returns_read_action()
    {
        $thread = $this->createGroupThread($this->tippin);

        $read = $this->composer->to($thread)->from($this->tippin)->read();

        $this->assertInstanceOf(MarkParticipantRead::class, $read);
    }

    /** @test */
    public function it_marks_read_and_creates_new_thread()
    {
        $this->composer->to($this->doe)->from($this->tippin)->read();

        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseCount('participants', 2);
    }

    /** @test */
    public function it_sends_typing_broadcast()
    {
        $thread = $this->createGroupThread($this->tippin);
        Event::fake(Typing::class);

        $this->composer->to($thread)->from($this->tippin)->emitTyping();

        Event::assertDispatched(function (Typing $event) use ($thread) {
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());
            $this->assertSame($this->tippin->getKey(), $event->broadcastWith()['provider_id']);

            return true;
        });
    }

    /** @test */
    public function it_sends_stopped_typing_broadcast()
    {
        $thread = $this->createGroupThread($this->tippin);
        Event::fake(StopTyping::class);

        $this->composer->to($thread)->from($this->tippin)->emitStopTyping();

        Event::assertDispatched(function (StopTyping $event) use ($thread) {
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());
            $this->assertSame($this->tippin->getKey(), $event->broadcastWith()['provider_id']);

            return true;
        });
    }

    /** @test */
    public function it_sends_read_broadcast()
    {
        $thread = $this->createGroupThread($this->tippin);
        Event::fake(Read::class);

        $this->composer->to($thread)->from($this->tippin)->emitRead();

        Event::assertDispatched(function (Read $event) use ($thread) {
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());
            $this->assertSame($this->tippin->getKey(), $event->broadcastWith()['provider_id']);

            return true;
        });
    }
}
