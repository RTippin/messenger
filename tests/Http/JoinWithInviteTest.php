<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\InviteUsedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class JoinWithInviteTest extends FeatureTestCase
{
    private Thread $group;
    private Invite $invite;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();
        $this->doe = $this->userDoe();
        $this->group = $this->createGroupThread($tippin, $this->doe);
        $this->invite = Invite::factory()
            ->for($this->group)
            ->owner($tippin)
            ->expires(now()->addHour())
            ->testing()
            ->create(['max_use' => 1]);
    }

    /** @test */
    public function missing_invite_is_not_found()
    {
        $this->getJson(route('api.messenger.invites.join', [
            'invite' => 'MISS4321',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function invalid_invite_yet_to_be_deleted_shows_invalid()
    {
        $this->invite->update([
            'uses' => 1,
        ]);

        $this->getJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->invite->id,
                'code' => 'TEST1234',
                'thread_id' => $this->group->id,
                'options' => [
                    'messenger_auth' => false,
                    'in_thread' => false,
                    'thread_name' => null,
                    'is_valid' => false,
                ],
            ]);
    }

    /** @test */
    public function invite_shows_invalid_when_not_deleted_and_past_expires()
    {
        $this->travel(2)->hours();

        $this->getJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->invite->id,
                'code' => 'TEST1234',
                'thread_id' => $this->group->id,
                'options' => [
                    'messenger_auth' => false,
                    'in_thread' => false,
                    'thread_name' => null,
                    'is_valid' => false,
                ],
            ]);
    }

    /** @test */
    public function guest_can_view_valid_invite()
    {
        $this->getJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->invite->id,
                'code' => 'TEST1234',
                'thread_id' => $this->group->id,
                'options' => [
                    'messenger_auth' => false,
                    'in_thread' => false,
                    'thread_name' => 'First Test Group',
                    'is_valid' => true,
                ],
            ]);
    }

    /** @test */
    public function non_participant_can_view_valid_invite()
    {
        $this->actingAs($this->companyDevelopers());

        $this->getJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->invite->id,
                'code' => 'TEST1234',
                'thread_id' => $this->group->id,
                'options' => [
                    'messenger_auth' => true,
                    'in_thread' => false,
                    'thread_name' => 'First Test Group',
                    'is_valid' => true,
                ],
            ]);
    }

    /** @test */
    public function existing_participant_viewing_invite_shows_in_thread()
    {
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->invite->id,
                'code' => 'TEST1234',
                'thread_id' => $this->group->id,
                'options' => [
                    'messenger_auth' => true,
                    'in_thread' => true,
                    'thread_name' => 'First Test Group',
                    'is_valid' => true,
                ],
            ]);
    }

    /** @test */
    public function non_participant_can_join_group_with_valid_invite()
    {
        $this->actingAs($this->createJaneSmith());

        $this->expectsEvents([
            InviteUsedEvent::class,
        ]);

        $this->postJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->group->id,
            ]);
    }

    /** @test */
    public function forbidden_to_join_group_with_valid_invite_when_disabled_from_group_settings()
    {
        $this->group->update([
            'invitations' => false,
        ]);
        $this->actingAs($this->companyDevelopers());

        $this->postJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_join_group_with_valid_invite_when_disabled_from_config()
    {
        Messenger::setThreadInvites(false);
        $this->actingAs($this->companyDevelopers());

        $this->postJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function existing_participant_forbidden_to_join_group_with_valid_invite()
    {
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_join_group_with_expired_but_not_deleted_invite()
    {
        $this->travel(2)->hours();
        $this->actingAs($this->companyDevelopers());

        $this->postJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertForbidden();
    }
}
