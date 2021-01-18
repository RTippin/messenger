<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreSystemMessageTest extends FeatureTestCase
{
    private Thread $group;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);
    }

    /** @test */
    public function system_message_stores_message()
    {
        app(StoreSystemMessage::class)->withoutDispatches()->execute(
            $this->group,
            $this->tippin,
            'system',
            'GROUP_CREATED'
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->group->id,
            'type' => 93,
            'body' => 'system',
        ]);
    }

    /** @test */
    public function system_message_updates_thread_timestamp()
    {
        $threadUpdatedAt = $this->group->updated_at->toDayDateTimeString();

        $this->travel(5)->minutes();

        app(StoreSystemMessage::class)->withoutDispatches()->execute(
            $this->group,
            $this->tippin,
            'system',
            'GROUP_CREATED'
        );

        $this->assertNotSame($threadUpdatedAt, $this->group->updated_at->toDayDateTimeString());
    }

    /** @test */
    public function system_message_fires_events()
    {
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        app(StoreSystemMessage::class)->execute(
            $this->group,
            $this->tippin,
            'system',
            'GROUP_CREATED'
        );

        Event::assertDispatched(function (NewMessageBroadcast $event) {
            $this->assertContains('private-user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (NewMessageEvent $event) {
            return $this->group->id === $event->message->thread_id;
        });
    }
}
