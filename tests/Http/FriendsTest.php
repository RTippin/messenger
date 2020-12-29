<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Events\FriendRemovedEvent;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\CompanyModel;
use RTippin\Messenger\Tests\stubs\UserModel;

class FriendsTest extends FeatureTestCase
{
    private Friend $friend;

    private Friend $inverseFriend;

    private Friend $friendCompany;

    private Friend $inverseFriendCompany;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupFriendship();
    }

    private function setupFriendship()
    {
        $friends = $this->makeFriends(
            UserModel::find(1),
            UserModel::find(2)
        );

        $this->friend = $friends[0];
        $this->inverseFriend = $friends[1];

        $friendsCompany = $this->makeFriends(
            UserModel::find(1),
            CompanyModel::find(1)
        );

        $this->friendCompany = $friendsCompany[0];
        $this->inverseFriendCompany = $friendsCompany[1];
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
        $this->actingAs($this->generateJaneSmith());

        $this->getJson(route('api.messenger.friends.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    /** @test */
    public function new_company_has_no_friends()
    {
        $this->actingAs($this->generateSomeCompany());

        $this->getJson(route('api.messenger.friends.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    /** @test */
    public function user_can_remove_friend()
    {
        Event::fake();

        $this->actingAs(UserModel::find(1));

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $this->friend->id,
        ]))
            ->assertSuccessful();

        Event::assertDispatched(function (FriendRemovedEvent $event) {
            $this->assertEquals($this->inverseFriend->id, $event->inverseFriend->id);
            $this->assertEquals($this->friend->id, $event->friend->id);
            return true;
        });

        $this->assertDatabaseMissing('friends', [
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
            'party_id' => 2,
            'party_type' => self::UserModelType,
        ]);

        $this->assertDatabaseMissing('friends', [
            'owner_id' => 2,
            'owner_type' => self::UserModelType,
            'party_id' => 1,
            'party_type' => self::UserModelType,
        ]);

        $this->assertEquals(0, resolve(FriendDriver::class)->friendStatus(UserModel::find(2)));
    }

    /** @test */
    public function user_can_remove_company_friend()
    {
        Event::fake();

        $this->actingAs(UserModel::find(1));

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $this->friendCompany->id,
        ]))
            ->assertSuccessful();

        Event::assertDispatched(function (FriendRemovedEvent $event) {
            $this->assertEquals($this->inverseFriendCompany->id, $event->inverseFriend->id);
            $this->assertEquals($this->friendCompany->id, $event->friend->id);
            return true;
        });

        $this->assertDatabaseMissing('friends', [
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
            'party_id' => 1,
            'party_type' => self::CompanyModelType,
        ]);

        $this->assertDatabaseMissing('friends', [
            'owner_id' => 1,
            'owner_type' => self::CompanyModelType,
            'party_id' => 1,
            'party_type' => self::UserModelType,
        ]);

        $this->assertEquals(0, resolve(FriendDriver::class)->friendStatus(CompanyModel::find(1)));
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
                'owner_type' => self::UserModelType,
                'party_id' => 2,
                'party_type' => self::UserModelType,
                'party' => [
                    'provider_id' => 2,
                    'provider_alias' => 'user',
                    'name' => 'John Doe',
                ],
            ]);
    }

    /** @test */
    public function user_can_view_company_friend()
    {
        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.friends.show', [
            'friend' => $this->friendCompany->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->friendCompany->id,
                'owner_id' => 1,
                'owner_type' => self::UserModelType,
                'party_id' => 1,
                'party_type' => self::CompanyModelType,
                'party' => [
                    'provider_id' => 1,
                    'provider_alias' => 'company',
                    'name' => 'Developers',
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

    /** @test */
    public function user_cannot_remove_inverse_company_friend()
    {
        $this->doesntExpectEvents([
            FriendRemovedEvent::class,
        ]);

        $this->actingAs(UserModel::find(1));

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $this->inverseFriendCompany->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_view_inverse_company_friend()
    {
        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.friends.show', [
            'friend' => $this->inverseFriendCompany->id,
        ]))
            ->assertForbidden();
    }
}
