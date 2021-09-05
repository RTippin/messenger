<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Friends\RemoveFriend;
use RTippin\Messenger\Broadcasting\FriendRemovedBroadcast;
use RTippin\Messenger\Events\FriendRemovedEvent;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class RemoveFriendTest extends FeatureTestCase
{
    use BroadcastLogger;

    /** @test */
    public function it_removes_friend_and_inverse_friend()
    {
        $friend = Friend::factory()->providers($this->tippin, $this->doe)->create();
        $inverse = Friend::factory()->providers($this->doe, $this->tippin)->create();

        app(RemoveFriend::class)->withoutDispatches()->execute($friend);

        $this->assertDatabaseMissing('friends', [
            'id' => $friend->id,
        ]);
        $this->assertDatabaseMissing('friends', [
            'id' => $inverse->id,
        ]);
    }

    /** @test */
    public function it_removes_friend_without_inverse_friend()
    {
        $friend = Friend::factory()->providers($this->tippin, $this->doe)->create();

        app(RemoveFriend::class)->withoutDispatches()->execute($friend);

        $this->assertDatabaseMissing('friends', [
            'id' => $friend->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            FriendRemovedBroadcast::class,
            FriendRemovedEvent::class,
        ]);
        $friend = Friend::factory()->providers($this->tippin, $this->doe)->create();
        $inverse = Friend::factory()->providers($this->doe, $this->tippin)->create();

        app(RemoveFriend::class)->execute($friend);

        Event::assertDispatched(function (FriendRemovedBroadcast $event) use ($inverse) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($inverse->id, $event->broadcastWith()['friend_id']);

            return true;
        });
        Event::assertDispatched(function (FriendRemovedEvent $event) use ($friend, $inverse) {
            $this->assertSame($inverse->id, $event->inverseFriend->id);
            $this->assertSame($friend->id, $event->friend->id);

            return true;
        });
        $this->logBroadcast(FriendRemovedBroadcast::class);
    }

    /** @test */
    public function it_doesnt_fire_broadcast_if_no_inverse_friend()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            FriendRemovedBroadcast::class,
            FriendRemovedEvent::class,
        ]);
        $friend = Friend::factory()->providers($this->tippin, $this->doe)->create();

        app(RemoveFriend::class)->execute($friend);

        Event::assertNotDispatched(FriendRemovedBroadcast::class);
        Event::assertDispatched(function (FriendRemovedEvent $event) {
            return is_null($event->inverseFriend);
        });
    }
}
