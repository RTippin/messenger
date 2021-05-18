<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreSystemMessageTest extends FeatureTestCase
{
    /** @test */
    public function it_stores_message()
    {
        $thread = Thread::factory()->group()->create();
        
        app(StoreSystemMessage::class)->execute($thread, $this->tippin, 'system', 'GROUP_CREATED');

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'type' => 93,
            'body' => 'system',
        ]);
    }

    /** @test */
    public function it_updates_thread_timestamp()
    {
        $thread = Thread::factory()->group()->create();
        $updated = now()->addMinutes(5)->format('Y-m-d H:i:s.u');
        Carbon::setTestNow($updated);

        app(StoreSystemMessage::class)->execute($thread, $this->tippin, 'system', 'GROUP_CREATED');

        $this->assertDatabaseHas('threads', [
            'id' => $thread->id,
            'updated_at' => $updated,
        ]);
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

        app(StoreSystemMessage::class)->execute($thread, $this->tippin, 'system', 'GROUP_CREATED');

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
     * @dataProvider messageTypes
     * @param $messageString
     * @param $messageInt
     */
    public function it_stores_message_type_using_description($messageString, $messageInt)
    {
        $thread = Thread::factory()->group()->create();

        app(StoreSystemMessage::class)->execute($thread, $this->tippin, 'system', $messageString);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'type' => $messageInt,
        ]);
    }

    public function messageTypes(): array
    {
        return [
            ['PARTICIPANT_JOINED_WITH_INVITE', 88],
            ['VIDEO_CALL', 90],
            ['GROUP_AVATAR_CHANGED', 91],
            ['THREAD_ARCHIVED', 92],
            ['GROUP_CREATED', 93],
            ['GROUP_RENAMED', 94],
            ['DEMOTED_ADMIN', 95],
            ['PROMOTED_ADMIN', 96],
            ['PARTICIPANT_LEFT_GROUP', 97],
            ['PARTICIPANT_REMOVED', 98],
            ['PARTICIPANTS_ADDED', 99],
        ];
    }
}
