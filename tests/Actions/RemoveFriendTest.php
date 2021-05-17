<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Friends\RemoveFriend;
use RTippin\Messenger\Events\FriendRemovedEvent;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Tests\FeatureTestCase;

class RemoveFriendTest extends FeatureTestCase
{
    /** @test */
    public function it_removes_inverse_friend()
    {
        $friend = Friend::factory()->providers($this->tippin, $this->doe)->create();
        $inverse = Friend::factory()->providers($this->doe, $this->tippin)->create();

        app(RemoveFriend::class)->withoutDispatches()->execute($inverse);

        $this->assertDatabaseMissing('friends', [
            'id' => $friend->id,
        ]);
        $this->assertDatabaseMissing('friends', [
            'id' => $inverse->id,
        ]);
    }

    /** @test */
    public function it_removes_friend()
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
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            FriendRemovedEvent::class,
        ]);
        $friend = Friend::factory()->providers($this->tippin, $this->doe)->create();
        $inverse = Friend::factory()->providers($this->doe, $this->tippin)->create();

        app(RemoveFriend::class)->execute($friend);

        Event::assertDispatched(function (FriendRemovedEvent $event) use ($friend, $inverse) {
            $this->assertSame($inverse->id, $event->inverseFriend->id);
            $this->assertSame($friend->id, $event->friend->id);

            return true;
        });
    }
}
