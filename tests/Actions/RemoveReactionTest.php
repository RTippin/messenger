<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Messages\RemoveReaction;
use RTippin\Messenger\Broadcasting\ReactionRemovedBroadcast;
use RTippin\Messenger\Events\ReactionRemovedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class RemoveReactionTest extends FeatureTestCase
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
        Messenger::setMessageReactions(false);
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->reacted()->create();
        $reaction = MessageReaction::factory()->for($message)->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Message reactions are currently disabled.');

        app(RemoveReaction::class)->execute($thread, $message, $reaction);
    }

    /** @test */
    public function it_removes_reaction()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->reacted()->create();
        MessageReaction::factory()->for($message)->owner($this->tippin)->create();
        $reaction = MessageReaction::factory()->for($message)->owner($this->tippin)->create();

        app(RemoveReaction::class)->execute($thread, $message, $reaction);

        $this->assertDatabaseMissing('message_reactions', [
            'id' => $reaction->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'reacted' => true,
        ]);
    }

    /** @test */
    public function it_removes_reaction_and_marks_message_not_reacted_when_last_one()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->reacted()->create();
        $reaction = MessageReaction::factory()->for($message)->owner($this->tippin)->create();

        app(RemoveReaction::class)->execute($thread, $message, $reaction);

        $this->assertDatabaseMissing('message_reactions', [
            'id' => $reaction->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'reacted' => false,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ReactionRemovedBroadcast::class,
            ReactionRemovedEvent::class,
        ]);
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->reacted()->create();
        $reaction = MessageReaction::factory()->for($message)->owner($this->tippin)->reaction(':joy:')->create();

        app(RemoveReaction::class)->execute($thread, $message, $reaction);

        Event::assertDispatched(function (ReactionRemovedBroadcast $event) use ($thread, $reaction) {
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());
            $this->assertSame($reaction->id, $event->broadcastWith()['id']);
            $this->assertSame(':joy:', $event->broadcastWith()['reaction']);

            return true;
        });
        Event::assertDispatchedTimes(ReactionRemovedBroadcast::class, 1);
        Event::assertDispatched(function (ReactionRemovedEvent $event) use ($reaction) {
            return $reaction->id === $event->reaction['id'];
        });
        $this->logBroadcast(ReactionRemovedBroadcast::class, 'Only uses presence channel when the message owner is the removed reactions owner.');
    }

    /** @test */
    public function it_fires_multiple_events_if_not_message_owner()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ReactionRemovedBroadcast::class,
            ReactionRemovedEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $message = Message::factory()->for($thread)->owner($this->doe)->reacted()->create();
        $reaction = MessageReaction::factory()->for($message)->owner($this->tippin)->create();

        app(RemoveReaction::class)->execute($thread, $message, $reaction);

        Event::assertDispatchedTimes(ReactionRemovedBroadcast::class, 2);
        Event::assertDispatched(function (ReactionRemovedEvent $event) use ($reaction) {
            return $reaction->id === $event->reaction['id'];
        });
        $this->logBroadcast(ReactionRemovedBroadcast::class, 'Uses both presence and the message owners private channel, when the removed reactions owner is not the message owner.');
    }

    /** @test */
    public function it_doesnt_fire_multiple_events_if_message_owner_not_in_thread()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ReactionRemovedBroadcast::class,
            ReactionRemovedEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->doe)->reacted()->create();
        $reaction = MessageReaction::factory()->for($message)->owner($this->tippin)->create();

        app(RemoveReaction::class)->execute($thread, $message, $reaction);

        Event::assertDispatchedTimes(ReactionRemovedBroadcast::class, 1);
    }
}
