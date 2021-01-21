<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreMessageTest extends FeatureTestCase
{
    private Thread $private;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function store_message_stores_message()
    {
        app(StoreMessage::class)->withoutDispatches()->execute(
            $this->private,
            'Hello World'
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 0,
            'body' => 'Hello World',
        ]);
    }

    /** @test */
    public function store_message_converts_emoji_to_shortcode()
    {
        app(StoreMessage::class)->withoutDispatches()->execute(
            $this->private,
            '👍 hello 💩'
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 0,
            'body' => ':thumbsup: hello :poop:',
        ]);
    }

    /** @test */
    public function store_message_sets_temporary_id_on_message()
    {
        $action = app(StoreMessage::class)->withoutDispatches()->execute(
            $this->private,
            'Hello World',
            '123-456-789'
        );

        $this->assertSame('123-456-789', $action->getMessage()->temporaryId());
    }

    /** @test */
    public function store_message_updates_thread_and_participant_timestamps()
    {
        $updated = now()->addMinutes(5);

        Carbon::setTestNow($updated);

        app(StoreMessage::class)->withoutDispatches()->execute(
            $this->private,
            'Hello World'
        );

        $participant = $this->private->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first();

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
    public function store_message_fires_events()
    {
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        app(StoreMessage::class)->execute(
            $this->private,
            'Hello World',
            '123-456-789'
        );

        Event::assertDispatched(function (NewMessageBroadcast $event) {
            $this->assertContains('private-user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($this->private->id, $event->broadcastWith()['thread_id']);
            $this->assertSame('123-456-789', $event->broadcastWith()['temporary_id']);

            return true;
        });

        Event::assertDispatched(function (NewMessageEvent $event) {
            return $this->private->id === $event->message->thread_id;
        });
    }
}
