<?php

namespace RTippin\Messenger\Tests\Messenger;

use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Broadcasting\ReactionAddedBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
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
    public function it_stores_message_with_existing_thread()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->composer->to($thread)->from($this->tippin)->message('Test');

        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_stores_message_and_creates_new_thread()
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
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->composer->to($thread)->from($this->tippin)->message('Test');
    }

    /** @test */
    public function it_sends_message_without_broadcast()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->doesntExpectEvents(NewMessageBroadcast::class);
        $this->expectsEvents(NewMessageEvent::class);

        $this->composer->to($thread)->from($this->tippin)->silent()->message('Test');
    }

    /** @test */
    public function it_stores_reaction()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $this->composer->to($thread)->from($this->tippin)->reaction($message, ':joy:');

        $this->assertDatabaseCount('message_reactions', 1);
    }

    /** @test */
    public function it_stores_reaction_with_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $this->expectsEvents([
            ReactionAddedBroadcast::class,
            ReactionAddedEvent::class,
        ]);

        $this->composer->to($thread)->from($this->tippin)->reaction($message, ':joy:');
    }

    /** @test */
    public function it_stores_reaction_without_broadcast()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $this->expectsEvents(ReactionAddedEvent::class);
        $this->doesntExpectEvents(ReactionAddedBroadcast::class);

        $this->composer->to($thread)->from($this->tippin)->silent()->reaction($message, ':joy:');
    }
}
