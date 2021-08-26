<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Messages\RemoveEmbeds;
use RTippin\Messenger\Broadcasting\EmbedsRemovedBroadcast;
use RTippin\Messenger\Events\EmbedsRemovedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class RemoveEmbedsTest extends FeatureTestCase
{
    use BroadcastLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_sets_message_embeds_to_false()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        app(RemoveEmbeds::class)->execute($thread, $message);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'embeds' => false,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            EmbedsRemovedBroadcast::class,
            EmbedsRemovedEvent::class,
        ]);
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        app(RemoveEmbeds::class)->execute($thread, $message);

        Event::assertDispatched(function (EmbedsRemovedBroadcast $event) use ($thread, $message) {
            $this->assertSame($message->id, $event->broadcastWith()['message_id']);
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());

            return true;
        });
        Event::assertDispatched(function (EmbedsRemovedEvent $event) use ($message) {
            return $message->id === $event->message->id;
        });
        $this->logBroadcast(EmbedsRemovedBroadcast::class);
    }
}
