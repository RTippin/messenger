<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Messages\RemoveReaction;
use RTippin\Messenger\Broadcasting\ReactionRemovedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ReactionRemovedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\MessageUnReacted;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class RemoveReactionTest extends FeatureTestCase
{
    private Thread $group;
    private Message $message;
    private MessageReaction $reaction;
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
        $this->group = $this->createGroupThread($this->tippin, $this->doe);
        $this->message = $this->createMessage($this->group, $this->tippin);
        $this->message->update(['reacted' => true]);
        $this->reaction = MessageReaction::factory()
            ->for($this->message)
            ->for($this->tippin, 'owner')
            ->create(['reaction' => ':joy:']);
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_removes_reaction()
    {
        $reaction = MessageReaction::factory()
            ->for($this->message)
            ->for($this->tippin, 'owner')
            ->create(['reaction' => ':poop:']);

        app(RemoveReaction::class)->withoutDispatches()->execute(
            $this->group,
            $this->message,
            $reaction
        );

        $this->assertDatabaseMissing('message_reactions', [
            'id' => $reaction->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => $this->message->id,
            'reacted' => true,
        ]);
    }

    /** @test */
    public function it_removes_reaction_and_marks_message_not_reacted_when_last_one()
    {
        app(RemoveReaction::class)->withoutDispatches()->execute(
            $this->group,
            $this->message,
            $this->reaction
        );

        $this->assertDatabaseMissing('message_reactions', [
            'id' => $this->reaction->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => $this->message->id,
            'reacted' => false,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            ReactionRemovedBroadcast::class,
            ReactionRemovedEvent::class,
        ]);
        Bus::fake();

        app(RemoveReaction::class)->execute(
            $this->group,
            $this->message,
            $this->reaction
        );

        Event::assertDispatched(function (ReactionRemovedBroadcast $event) {
            $this->assertContains('presence-messenger.thread.'.$this->group->id, $event->broadcastOn());
            $this->assertSame($this->reaction->id, $event->broadcastWith()['reaction_id']);
            $this->assertSame(':joy:', $event->broadcastWith()['reaction']);

            return true;
        });
        Event::assertDispatched(function (ReactionRemovedEvent $event) {
            return $this->reaction->id === $event->reaction['id'];
        });
        Bus::assertNotDispatched(CallQueuedListener::class);
    }

    /** @test */
    public function it_dispatches_listeners_if_not_message_owner()
    {
        Bus::fake();
        Messenger::setProvider($this->doe);

        app(RemoveReaction::class)->execute(
            $this->group,
            $this->message,
            $this->reaction
        );

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === MessageUnReacted::class;
        });
    }
}
