<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreMessageTest extends FeatureTestCase
{
    private Thread $private;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_stores_message()
    {
        app(StoreMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'message' => 'Hello World',
            ]
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 0,
            'body' => 'Hello World',
        ]);
    }

    /** @test */
    public function it_converts_emoji_to_shortcode()
    {
        app(StoreMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'message' => '👍 hello 💩',
            ]
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 0,
            'body' => ':thumbsup: hello :poop:',
        ]);
    }

    /** @test */
    public function it_sets_temporary_id_on_message()
    {
        $action = app(StoreMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'message' => 'Hello World',
                'temporary_id' => '123-456-789',
            ]
        );

        $this->assertSame('123-456-789', $action->getMessage()->temporaryId());
    }

    /** @test */
    public function it_can_add_extra_data_on_message()
    {
        app(StoreMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'message' => 'Extra',
                'temporary_id' => '123-456-789',
                'extra' => ['test' => true],
            ]
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 0,
            'body' => 'Extra',
            'extra' => '{"test":true}',
        ]);
    }

    /** @test */
    public function it_can_reply_to_existing_message()
    {
        $message = $this->createMessage($this->private, $this->tippin);

        app(StoreMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'message' => 'Replying',
                'temporary_id' => '123-456-789',
                'reply_to_id' => $message->id,
            ]
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 0,
            'body' => 'Replying',
            'reply_to_id' => $message->id,
        ]);
    }

    /** @test */
    public function it_will_not_add_reply_if_system_message()
    {
        $system = Message::factory()->for($this->private)->owner($this->tippin)->create([
            'body' => 'System Message',
            'type' => 93,
        ]);

        app(StoreMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'message' => 'Replying',
                'temporary_id' => '123-456-789',
                'reply_to_id' => $system->id,
            ]
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 0,
            'body' => 'Replying',
            'reply_to_id' => null,
        ]);
    }

    /** @test */
    public function it_will_not_add_reply_if_message_not_found()
    {
        app(StoreMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'message' => 'Replying',
                'temporary_id' => '123-456-789',
                'reply_to_id' => 404,
            ]
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 0,
            'body' => 'Replying',
            'reply_to_id' => null,
        ]);
    }

    /** @test */
    public function it_will_not_add_reply_if_message_from_another_thread()
    {
        $group = $this->createGroupThread($this->tippin);
        $message = $this->createMessage($group, $this->tippin);

        app(StoreMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'message' => 'Replying',
                'temporary_id' => '123-456-789',
                'reply_to_id' => $message->id,
            ]
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 0,
            'body' => 'Replying',
            'reply_to_id' => null,
        ]);
    }

    /** @test */
    public function it_updates_thread_and_participant()
    {
        $updated = now()->addMinutes(5)->format('Y-m-d H:i:s.u');
        Carbon::setTestNow($updated);

        app(StoreMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'message' => 'Hello World',
            ]
        );

        $participant = $this->private->participants()->forProvider($this->tippin)->first();

        $this->assertDatabaseHas('threads', [
            'id' => $this->private->id,
            'updated_at' => $updated,
        ]);
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'last_read' => $updated,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        app(StoreMessage::class)->execute(
            $this->private,
            [
                'message' => 'Hello World',
                'temporary_id' => '123-456-789',
            ]
        );

        Event::assertDispatched(function (NewMessageBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($this->private->id, $event->broadcastWith()['thread_id']);
            $this->assertSame('123-456-789', $event->broadcastWith()['temporary_id']);

            return true;
        });
        Event::assertDispatched(function (NewMessageEvent $event) {
            return $this->private->id === $event->message->thread_id;
        });
    }
}
