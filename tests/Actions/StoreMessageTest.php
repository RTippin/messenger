<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreMessageTest extends FeatureTestCase
{
    use BroadcastLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_stores_message()
    {
        $thread = Thread::factory()->create();

        app(StoreMessage::class)->execute($thread, [
            'message' => 'Hello World',
        ]);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'type' => 0,
            'body' => 'Hello World',
            'extra' => null,
        ]);
    }

    /** @test */
    public function it_converts_emoji_to_shortcode()
    {
        $thread = Thread::factory()->create();

        app(StoreMessage::class)->execute($thread, [
            'message' => '👍 hello 💩',
        ]);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'type' => 0,
            'body' => ':thumbsup: hello :poop:',
        ]);
    }

    /** @test */
    public function it_sets_temporary_id_on_message()
    {
        $action = app(StoreMessage::class)->execute(Thread::factory()->create(), [
            'message' => 'Hello World',
            'temporary_id' => '123-456-789',
        ]);

        $this->assertSame('123-456-789', $action->getMessage()->temporaryId());
    }

    /** @test */
    public function it_can_add_extra_data_on_message()
    {
        $thread = Thread::factory()->create();

        app(StoreMessage::class)->execute($thread, [
            'message' => 'Extra',
            'extra' => ['test' => true],
        ]);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'type' => 0,
            'body' => 'Extra',
            'extra' => '{"test":true}',
        ]);
    }

    /** @test */
    public function it_can_store_null_message_body()
    {
        $thread = Thread::factory()->create();

        app(StoreMessage::class)->execute($thread, [
            'message' => null,
        ]);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'type' => 0,
            'body' => null,
            'extra' => null,
        ]);
    }

    /** @test */
    public function it_can_reply_to_existing_message()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        app(StoreMessage::class)->execute($thread, [
            'message' => 'Replying',
            'reply_to_id' => $message->id,
        ]);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'type' => 0,
            'body' => 'Replying',
            'reply_to_id' => $message->id,
        ]);
    }

    /** @test */
    public function it_will_not_add_reply_if_system_message()
    {
        $thread = Thread::factory()->create();
        $system = Message::factory()->for($thread)->owner($this->tippin)->system()->create();

        app(StoreMessage::class)->execute($thread, [
            'message' => 'Replying',
            'reply_to_id' => $system->id,
        ]);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'type' => 0,
            'body' => 'Replying',
            'reply_to_id' => null,
        ]);
    }

    /** @test */
    public function it_will_not_add_reply_if_message_not_found()
    {
        $thread = Thread::factory()->create();

        app(StoreMessage::class)->execute($thread, [
            'message' => 'Replying',
            'reply_to_id' => 404,
        ]);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'type' => 0,
            'body' => 'Replying',
            'reply_to_id' => null,
        ]);
    }

    /** @test */
    public function it_will_not_add_reply_if_message_from_another_thread()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for(Thread::factory()->create())->owner($this->doe)->create();

        app(StoreMessage::class)->execute($thread, [
            'message' => 'Replying',
            'reply_to_id' => $message->id,
        ]);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'type' => 0,
            'body' => 'Replying',
            'reply_to_id' => null,
        ]);
    }

    /** @test */
    public function it_updates_thread_and_participant()
    {
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();
        $updated = now()->addMinutes(5)->format('Y-m-d H:i:s.u');
        Carbon::setTestNow($updated);

        app(StoreMessage::class)->execute($thread, [
            'message' => 'Hello World',
        ]);

        $this->assertDatabaseHas('threads', [
            'id' => $thread->id,
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
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        app(StoreMessage::class)->execute($thread, [
            'message' => 'Hello World',
        ]);

        Event::assertDispatched(function (NewMessageBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (NewMessageEvent $event) use ($thread) {
            $this->assertNull($event->senderIp);

            return $thread->id === $event->message->thread_id;
        });
        $this->logBroadcast(NewMessageBroadcast::class, 'Text message.');
    }

    /** @test */
    public function it_fires_event_with_sender_ip()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        app(StoreMessage::class)->execute($thread, [
            'message' => 'Hello World',
        ], '1.2.3.4');

        Event::assertDispatched(function (NewMessageEvent $event) {
            return '1.2.3.4' === $event->senderIp;
        });
    }
}
