<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Messages\RemoveEmbeds;
use RTippin\Messenger\Broadcasting\EmbedsRemovedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\EmbedsRemovedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class RemoveEmbedsTest extends FeatureTestCase
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
    public function it_sets_message_embeds_to_false()
    {
        app(RemoveEmbeds::class)->withoutDispatches()->execute(
            $this->group,
            $this->message
        );

        $this->assertDatabaseHas('messages', [
            'id' => $this->message->id,
            'embeds' => false,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            EmbedsRemovedBroadcast::class,
            EmbedsRemovedEvent::class,
        ]);

        app(RemoveEmbeds::class)->execute(
            $this->group,
            $this->message
        );

        Event::assertDispatched(function (EmbedsRemovedBroadcast $event) {
            $this->assertSame($this->message->id, $event->broadcastWith()['message_id']);
            $this->assertContains('presence-messenger.thread.'.$this->group->id, $event->broadcastOn());

            return true;
        });
        Event::assertDispatched(function (EmbedsRemovedEvent $event) {
            return $this->message->id === $event->message->id;
        });
    }
}
