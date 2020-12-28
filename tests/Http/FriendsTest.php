<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Events\FriendRemovedEvent;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class FriendsTest extends FeatureTestCase
{
    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.friends.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function new_user_has_no_friends()
    {
        $this->actingAs(UserModel::first());

        $this->getJson(route('api.messenger.friends.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    /** @test */
    public function user_can_remove_friend()
    {
        $this->expectsEvents([
            FriendRemovedEvent::class,
        ]);

        $users = UserModel::all();

        $friends = resolve(FriendDriver::class);

        $friend = Friend::create([
            'owner_id' => $users->first()->getKey(),
            'owner_type' => get_class($users->first()),
            'party_id' => $users->last()->getKey(),
            'party_type' => get_class($users->last()),
        ]);

        Friend::create([
            'owner_id' => $users->last()->getKey(),
            'owner_type' => get_class($users->last()),
            'party_id' => $users->first()->getKey(),
            'party_type' => get_class($users->first()),
        ]);

        $this->actingAs($users->first());

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $friend->getKey(),
        ]))
            ->assertSuccessful();

        $this->assertDatabaseMissing('friends', [
            'owner_id' => $users->first()->getKey(),
            'party_id' => $users->last()->getKey(),
        ]);

        $this->assertDatabaseMissing('friends', [
            'owner_id' => $users->last()->getKey(),
            'party_id' => $users->first()->getKey(),
        ]);

        $this->assertEquals(0, $friends->friendStatus($users->first()));
    }
}
