<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
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

        $this->setupFriendship();
    }

    private function setupFriendship()
    {
        $friends = $this->makeFriends(
            $this->userTippin(),
            $this->userDoe()
        );

        $this->friend = $friends[0];
        $this->inverseFriend = $friends[1];

        $friendsCompany = $this->makeFriends(
            $this->userTippin(),
            $this->companyDevelopers()
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
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            FriendRemovedEvent::class,
        ]);

        $this->actingAs($tippin);

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
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'party_id' => $doe->getKey(),
            'party_type' => get_class($doe),
        ]);

        $this->assertDatabaseMissing('friends', [
            'owner_id' => $doe->getKey(),
            'owner_type' => get_class($doe),
            'party_id' => $tippin->getKey(),
            'party_type' => get_class($tippin),
        ]);

        $this->assertEquals(0, resolve(FriendDriver::class)->friendStatus($doe));
    }

    /** @test */
    public function user_can_remove_company_friend()
    {
        $tippin = $this->userTippin();

        $developers = $this->companyDevelopers();

        Event::fake([
            FriendRemovedEvent::class,
        ]);

        $this->actingAs($tippin);

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
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'party_id' => $developers->getKey(),
            'party_type' => get_class($developers),
        ]);

        $this->assertDatabaseMissing('friends', [
            'owner_id' => $developers->getKey(),
            'owner_type' => get_class($developers),
            'party_id' => $tippin->getKey(),
            'party_type' => get_class($tippin),
        ]);

        $this->assertEquals(0, resolve(FriendDriver::class)->friendStatus($developers));
    }

    /** @test */
    public function user_can_view_friend()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.friends.show', [
            'friend' => $this->friend->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->friend->id,
                'owner_id' => $tippin->getKey(),
                'owner_type' => get_class($tippin),
                'party_id' => $doe->getKey(),
                'party_type' => get_class($doe),
                'party' => [
                    'provider_id' => $doe->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'John Doe',
                ],
            ]);
    }

    /** @test */
    public function user_can_view_company_friend()
    {
        $tippin = $this->userTippin();

        $developers = $this->companyDevelopers();

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.friends.show', [
            'friend' => $this->friendCompany->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->friendCompany->id,
                'owner_id' => $tippin->getKey(),
                'owner_type' => get_class($tippin),
                'party_id' => $developers->getKey(),
                'party_type' => get_class($developers),
                'party' => [
                    'provider_id' => $developers->getKey(),
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

        $this->actingAs($this->userTippin());

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $this->inverseFriend->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_view_inverse_friend()
    {
        $this->actingAs($this->userTippin());

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

        $this->actingAs($this->userTippin());

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $this->inverseFriendCompany->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_view_inverse_company_friend()
    {
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.friends.show', [
            'friend' => $this->inverseFriendCompany->id,
        ]))
            ->assertForbidden();
    }
}
