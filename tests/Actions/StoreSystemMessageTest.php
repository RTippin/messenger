<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreSystemMessageTest extends FeatureTestCase
{
    /** @test */
    public function it_stores_message()
    {
        $thread = Thread::factory()->group()->create();

        app(StoreSystemMessage::class)->execute($thread, $this->tippin, 'system', Message::GROUP_CREATED);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'type' => Message::GROUP_CREATED,
            'body' => 'system',
        ]);
    }

    /** @test */
    public function it_updates_thread_timestamp()
    {
        $thread = Thread::factory()->group()->create();
        $updated = now()->addMinutes(5)->format('Y-m-d H:i:s.u');
        Carbon::setTestNow($updated);

        app(StoreSystemMessage::class)->execute($thread, $this->tippin, 'system', Message::GROUP_CREATED);

        $this->assertDatabaseHas('threads', [
            'id' => $thread->id,
            'updated_at' => $updated,
        ]);
    }

    /** @test */
    public function it_does_nothing_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        Messenger::setSystemMessages(false);
        $thread = Thread::factory()->group()->create();

        app(StoreSystemMessage::class)->execute($thread, $this->tippin, 'system', Message::GROUP_CREATED);

        $this->assertDatabaseCount('messages', 0);
        Event::assertNotDispatched(NewMessageBroadcast::class);
        Event::assertNotDispatched(NewMessageEvent::class);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        app(StoreSystemMessage::class)->execute($thread, $this->tippin, 'system', Message::GROUP_CREATED);

        Event::assertDispatched(function (NewMessageBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (NewMessageEvent $event) use ($thread) {
            return $thread->id === $event->message->thread_id;
        });
    }

    /**
     * @test
     *
     * @dataProvider messageTypes
     *
     * @param  $type
     */
    public function it_stores_message_type_using_description($type)
    {
        $thread = Thread::factory()->group()->create();

        app(StoreSystemMessage::class)->execute($thread, $this->tippin, 'system', $type);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'type' => $type,
        ]);
    }

    public static function messageTypes(): array
    {
        return [
            [Message::PARTICIPANT_JOINED_WITH_INVITE],
            [Message::VIDEO_CALL],
            [Message::GROUP_AVATAR_CHANGED],
            [Message::THREAD_ARCHIVED],
            [Message::GROUP_CREATED],
            [Message::GROUP_RENAMED],
            [Message::DEMOTED_ADMIN],
            [Message::PROMOTED_ADMIN],
            [Message::PARTICIPANT_LEFT_GROUP],
            [Message::PARTICIPANT_REMOVED],
            [Message::PARTICIPANTS_ADDED],
            [Message::BOT_ADDED],
            [Message::BOT_RENAMED],
            [Message::BOT_AVATAR_CHANGED],
            [Message::BOT_REMOVED],
            [Message::BOT_PACKAGE_INSTALLED],
        ];
    }
}
