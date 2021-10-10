<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Messages\EditMessage;
use RTippin\Messenger\Broadcasting\MessageEditedBroadcast;
use RTippin\Messenger\Events\MessageEditedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class EditMessageTest extends FeatureTestCase
{
    use BroadcastLogger;

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setMessageEdits(false);
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Edit messages are currently disabled.');

        app(EditMessage::class)->execute($thread, $message, 'Edited');
    }

    /** @test */
    public function it_updates_message_and_stores_edit_while_resetting_message_reply_cache()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => 'Original']);
        $editedAt = now()->addMinutes(5)->format('Y-m-d H:i:s.u');
        $cache = Cache::spy();
        Carbon::setTestNow($editedAt);

        app(EditMessage::class)->execute($thread, $message, 'Edited');

        $cache->shouldHaveReceived('forget')->with('reply:message:'.$message->id);
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'body' => 'Edited',
            'edited' => true,
            'updated_at' => $editedAt,
        ]);
        $this->assertDatabaseHas('message_edits', [
            'message_id' => $message->id,
            'body' => 'Original',
            'edited_at' => $editedAt,
        ]);
    }

    /** @test */
    public function it_can_store_previous_null_message_body()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => null]);

        app(EditMessage::class)->execute($thread, $message, 'Edited');

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'body' => 'Edited',
        ]);
        $this->assertDatabaseHas('message_edits', [
            'message_id' => $message->id,
            'body' => null,
        ]);
    }

    /** @test */
    public function it_converts_emoji_to_shortcode()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        app(EditMessage::class)->execute($thread, $message, 'Edited 👍');

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'body' => 'Edited :thumbsup:',
        ]);
    }

    /** @test */
    public function it_doesnt_fire_events_or_reset_cache_key_if_message_does_not_change()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            MessageEditedBroadcast::class,
            MessageEditedEvent::class,
        ]);
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => 'Unchanged']);
        $cache = Cache::spy();

        app(EditMessage::class)->execute($thread, $message, 'Unchanged');

        $cache->shouldNotHaveReceived('forget');
        $this->assertDatabaseCount('message_edits', 0);
        Event::assertNotDispatched(MessageEditedBroadcast::class);
        Event::assertNotDispatched(MessageEditedEvent::class);
    }

    /** @test */
    public function it_fires_events_if_message_changed()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            MessageEditedBroadcast::class,
            MessageEditedEvent::class,
        ]);
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => 'Original']);

        app(EditMessage::class)->execute($thread, $message, 'Edited');

        Event::assertDispatched(function (MessageEditedBroadcast $event) use ($thread, $message) {
            $this->assertSame($message->id, $event->broadcastWith()['id']);
            $this->assertTrue($event->broadcastWith()['edited']);
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());

            return true;
        });
        Event::assertDispatched(function (MessageEditedEvent $event) use ($message) {
            $this->assertSame($message->id, $event->message->id);
            $this->assertSame('Original', $event->originalBody);

            return true;
        });
        $this->logBroadcast(MessageEditedBroadcast::class);
    }

    /** @test */
    public function it_fires_events_if_message_changed_and_previously_null()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            MessageEditedBroadcast::class,
            MessageEditedEvent::class,
        ]);
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => null]);

        app(EditMessage::class)->execute($thread, $message, 'Edited');

        Event::assertDispatched(MessageEditedBroadcast::class);
        Event::assertDispatched(function (MessageEditedEvent $event) use ($message) {
            $this->assertSame($message->id, $event->message->id);
            $this->assertNull($event->originalBody);

            return true;
        });
    }
}
