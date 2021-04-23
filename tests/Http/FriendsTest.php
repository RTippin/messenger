<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Events\FriendRemovedEvent;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Tests\FeatureTestCase;

class FriendsTest extends FeatureTestCase
{
    private Friend $friend;
    private Friend $inverseFriend;
    private Friend $friendCompany;
    private Friend $inverseFriendCompany;

    protected function setUp(): void
    {
        parent::setUp();

        $friends = $this->createFriends($this->tippin, $this->doe);
        $this->friend = $friends[0];
        $this->inverseFriend = $friends[1];
        $friendsCompany = $this->createFriends($this->tippin, $this->developers);
        $this->friendCompany = $friendsCompany[0];
        $this->inverseFriendCompany = $friendsCompany[1];
    }

    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.friends.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function new_user_has_no_friends()
    {
        $this->actingAs($this->createJaneSmith());

        $this->getJson(route('api.messenger.friends.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    /** @test */
    public function new_company_has_no_friends()
    {
        $this->actingAs($this->createSomeCompany());

        $this->getJson(route('api.messenger.friends.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    /** @test */
    public function user_can_remove_friend()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            FriendRemovedEvent::class,
        ]);

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $this->friend->id,
        ]))
            ->assertSuccessful();

        $this->assertSame(0, resolve(FriendDriver::class)->friendStatus($this->doe));
    }

    /** @test */
    public function user_can_remove_company_friend()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            FriendRemovedEvent::class,
        ]);

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $this->friendCompany->id,
        ]))
            ->assertSuccessful();

        $this->assertSame(0, resolve(FriendDriver::class)->friendStatus($this->developers));
    }

    /** @test */
    public function user_can_view_friend()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.friends.show', [
            'friend' => $this->friend->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->friend->id,
                'owner_id' => $this->tippin->getKey(),
                'owner_type' => $this->tippin->getMorphClass(),
                'party_id' => $this->doe->getKey(),
                'party_type' => $this->doe->getMorphClass(),
                'party' => [
                    'provider_id' => $this->doe->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'John Doe',
                ],
            ]);
    }

    /** @test */
    public function user_can_view_company_friend()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.friends.show', [
            'friend' => $this->friendCompany->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->friendCompany->id,
                'owner_id' => $this->tippin->getKey(),
                'owner_type' => $this->tippin->getMorphClass(),
                'party_id' => $this->developers->getKey(),
                'party_type' => $this->developers->getMorphClass(),
                'party' => [
                    'provider_id' => $this->developers->getKey(),
                    'provider_alias' => 'company',
                    'name' => 'Developers',
                ],
            ]);
    }

    /** @test */
    public function user_cannot_remove_inverse_friend()
    {
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $this->inverseFriend->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_view_inverse_friend()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.friends.show', [
            'friend' => $this->inverseFriend->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_remove_inverse_company_friend()
    {
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $this->inverseFriendCompany->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_view_inverse_company_friend()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.friends.show', [
            'friend' => $this->inverseFriendCompany->id,
        ]))
            ->assertForbidden();
    }
}
