<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Messages\AddReaction;
use RTippin\Messenger\Broadcasting\ReactionAddedBroadcast;
use RTippin\Messenger\Events\ReactionAddedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\ReactionException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Tests\FeatureTestCase;

class AddReactionTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setMessageReactions(false);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Message reactions are currently disabled.');

        app(AddReaction::class)->execute($thread, $message, ':joy:');
    }

    /** @test */
    public function it_throws_exception_if_no_valid_emojis()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $this->expectException(ReactionException::class);
        $this->expectExceptionMessage('No valid reactions found.');

        app(AddReaction::class)->execute($thread, $message, ':unknown:');
    }

    /** @test */
    public function it_throws_exception_if_reaction_already_exist()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        MessageReaction::factory()->for($message)->owner($this->tippin)->reaction(':joy:')->create();

        $this->expectException(ReactionException::class);
        $this->expectExceptionMessage('You have already used that reaction.');

        app(AddReaction::class)->execute($thread, $message, ':joy:');
    }

    /** @test */
    public function it_throws_exception_if_reaction_exceeds_max_unique()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        MessageReaction::factory()
            ->for($message)
            ->owner($this->tippin)
            ->state(new Sequence(
                ['reaction' => ':one:'],
                ['reaction' => ':two:'],
                ['reaction' => ':three:'],
                ['reaction' => ':four:'],
                ['reaction' => ':five:'],
                ['reaction' => ':six:'],
                ['reaction' => ':seven:'],
                ['reaction' => ':eight:'],
                ['reaction' => ':nine:'],
                ['reaction' => ':ten:'],
            ))
            ->count(10)
            ->create();

        $this->expectException(ReactionException::class);
        $this->expectExceptionMessage('We appreciate the enthusiasm, but there are already too many reactions on this message.');

        app(AddReaction::class)->execute($thread, $message, ':joy:');
    }

    /** @test */
    public function it_stores_reaction_and_marks_message_reacted()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        app(AddReaction::class)->execute($thread, $message, ':joy:');

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $message->id,
            'reaction' => ':joy:',
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'reacted' => true,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ReactionAddedBroadcast::class,
            ReactionAddedEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        app(AddReaction::class)->execute($thread, $message, ':joy:');

        Event::assertDispatched(function (ReactionAddedBroadcast $event) use ($thread, $message) {
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());
            $this->assertSame($message->id, $event->broadcastWith()['message_id']);
            $this->assertSame(':joy:', $event->broadcastWith()['reaction']);

            return true;
        });
        Event::assertDispatchedTimes(ReactionAddedBroadcast::class, 1);
        Event::assertDispatched(function (ReactionAddedEvent $event) use ($message) {
            return $message->id === $event->reaction->message_id;
        });
    }

    /** @test */
    public function it_fires_multiple_events_if_not_message_owner()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ReactionAddedBroadcast::class,
            ReactionAddedEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $message = Message::factory()->for($thread)->owner($this->doe)->create();

        app(AddReaction::class)->execute($thread, $message, ':joy:');

        Event::assertDispatchedTimes(ReactionAddedBroadcast::class, 2);
        Event::assertDispatched(function (ReactionAddedEvent $event) use ($message) {
            return $message->id === $event->reaction->message_id;
        });
    }

    /** @test */
    public function it_doesnt_fire_multiple_events_if_message_owner_not_in_thread()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ReactionAddedBroadcast::class,
            ReactionAddedEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->doe)->create();

        app(AddReaction::class)->execute($thread, $message, ':joy:');

        Event::assertDispatchedTimes(ReactionAddedBroadcast::class, 1);
        Event::assertDispatched(function (ReactionAddedEvent $event) use ($message) {
            return $message->id === $event->reaction->message_id;
        });
    }
}
