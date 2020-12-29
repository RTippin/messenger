<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Events\FriendRemovedEvent;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\UserModel;

class FriendsTest extends FeatureTestCase
{
    private Friend $friend;

    private Friend $inverseFriend;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupFriendship();
    }

    private function setupFriendship()
    {
        $this->friend = Friend::create([
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
            'party_id' => 2,
            'party_type' => self::UserModelType,
        ]);

        $this->inverseFriend = Friend::create([
            'owner_id' => 2,
            'owner_type' => self::UserModelType,
            'party_id' => 1,
            'party_type' => self::UserModelType,
        ]);
    }

    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.friends.index'))
            ->assertUnauthorized();

        $this->getJson(route('api.messenger.friends.show', [
            'friend' => $this->friend->id,
        ]))
            ->assertUnauthorized();
    }

    /** @test */
    public function new_user_has_no_friends()
    {
        $newUser = $this->generateJaneSmith();

        $this->actingAs($newUser);

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

        $this->actingAs(UserModel::find(1));

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $this->friend->id,
        ]))
            ->assertSuccessful();

        $this->assertDatabaseMissing('friends', [
            'owner_id' => 1,
            'party_id' => 2,
        ]);

        $this->assertDatabaseMissing('friends', [
            'owner_id' => 2,
            'party_id' => 1,
        ]);

        $this->assertEquals(0, resolve(FriendDriver::class)->friendStatus(UserModel::find(2)));
    }

    /** @test */
    public function user_can_view_friend()
    {
        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.friends.show', [
            'friend' => $this->friend->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->friend->id,
                'owner_id' => 1,
                'party_id' => 2,
                'party' => [
                    'name' => 'John Doe',
                ],
            ]);
    }

    /** @test */
    public function user_cannot_remove_inverse_friend()
    {
        $this->doesntExpectEvents([
            FriendRemovedEvent::class,
        ]);

        $this->actingAs(UserModel::find(1));

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $this->inverseFriend->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_view_inverse_friend()
    {
        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.friends.show', [
            'friend' => $this->inverseFriend->id,
        ]))
            ->assertForbidden();
    }
}
