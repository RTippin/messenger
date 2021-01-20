<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Friends\RemoveFriend;
use RTippin\Messenger\Events\FriendRemovedEvent;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Tests\FeatureTestCase;

class RemoveFriendTest extends FeatureTestCase
{
    private Friend $friend;

    private Friend $inverseFriend;

    protected function setUp(): void
    {
        parent::setUp();

        $friends = $this->createFriends($this->userTippin(), $this->userDoe());

        $this->friend = $friends[0];

        $this->inverseFriend = $friends[1];
    }

    /** @test */
    public function remove_friend_removes_inverse_friend()
    {
        app(RemoveFriend::class)->withoutDispatches()->execute($this->friend);

        $this->assertDatabaseMissing('friends', [
            'id' => $this->friend->id,
        ]);

        $this->assertDatabaseMissing('friends', [
            'id' => $this->inverseFriend->id,
        ]);
    }

    /** @test */
    public function remove_inverse_friend_removes_friend()
    {
        app(RemoveFriend::class)->withoutDispatches()->execute($this->inverseFriend);

        $this->assertDatabaseMissing('friends', [
            'id' => $this->friend->id,
        ]);

        $this->assertDatabaseMissing('friends', [
            'id' => $this->inverseFriend->id,
        ]);
    }

    /** @test */
    public function remove_friend_fires_event()
    {
        Event::fake([
            FriendRemovedEvent::class,
        ]);

        app(RemoveFriend::class)->execute($this->friend);

        Event::assertDispatched(function (FriendRemovedEvent $event) {
            $this->assertSame($this->inverseFriend->id, $event->inverseFriend->id);
            $this->assertSame($this->friend->id, $event->friend->id);

            return true;
        });
    }
}
