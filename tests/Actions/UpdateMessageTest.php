<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Messages\UpdateMessage;
use RTippin\Messenger\Broadcasting\MessageEditedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\MessageEditedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\StoreMessageEdit;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateMessageTest extends FeatureTestCase
{
    private Thread $group;

    private Message $message;

    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->group = $this->createGroupThread($this->tippin);

        $this->message = $this->createMessage($this->group, $this->tippin);

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function update_message_updates_message()
    {
        app(UpdateMessage::class)->withoutDispatches()->execute(
            $this->group,
            $this->message,
            'Edited Message'
        );

        $this->assertDatabaseHas('messages', [
            'id' => $this->message->id,
            'body' => 'Edited Message',
        ]);
    }

    /** @test */
    public function update_message_converts_emoji_to_shortcode()
    {
        app(UpdateMessage::class)->withoutDispatches()->execute(
            $this->group,
            $this->message,
            'Edited 👍'
        );

        $this->assertDatabaseHas('messages', [
            'id' => $this->message->id,
            'body' => 'Edited :thumbsup:',
        ]);
    }

    /** @test */
    public function update_message_fires_no_events_if_message_does_not_change()
    {
        $this->doesntExpectEvents([
            MessageEditedBroadcast::class,
            MessageEditedEvent::class,
        ]);

        app(UpdateMessage::class)->execute(
            $this->group,
            $this->message,
            'First Test Message'
        );
    }

    /** @test */
    public function update_message_fires_events()
    {
        Event::fake([
            MessageEditedBroadcast::class,
            MessageEditedEvent::class,
        ]);

        $this->travel(5)->minutes();

        app(UpdateMessage::class)->execute(
            $this->group,
            $this->message,
            'Edited Message'
        );

        Event::assertDispatched(function (MessageEditedBroadcast $event) {
            $this->assertSame($this->message->id, $event->broadcastWith()['id']);
            $this->assertTrue($event->broadcastWith()['edited']);
            $this->assertContains('presence-messenger.thread.'.$this->group->id, $event->broadcastOn());

            return true;
        });

        Event::assertDispatched(function (MessageEditedEvent $event) {
            $this->assertSame($this->message->id, $event->message->id);
            $this->assertSame('First Test Message', $event->originalBody);

            return true;
        });
    }

    /** @test */
    public function update_message_triggers_listener()
    {
        Bus::fake();

        app(UpdateMessage::class)->withoutBroadcast()->execute(
            $this->group,
            $this->message,
            'Edited Message'
        );

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === StoreMessageEdit::class;
        });
    }
}
