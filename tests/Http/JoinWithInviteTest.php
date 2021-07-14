<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class JoinWithInviteTest extends HttpTestCase
{
    /** @test */
    public function missing_invite_is_not_found()
    {
        $this->logCurrentRequest();
        $this->getJson(route('api.messenger.invites.join', [
            'invite' => 'MISS4321',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function invalid_invite_yet_to_be_deleted_shows_invalid()
    {
        $thread = Thread::factory()->group()->create();
        $invite = Invite::factory()->for($thread)->owner($this->tippin)->invalid()->testing()->create();

        $this->getJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $invite->id,
                'thread_id' => $thread->id,
                'options' => [
                    'is_valid' => false,
                ],
            ]);
    }

    /** @test */
    public function invite_shows_invalid_when_not_deleted_and_past_expires()
    {
        $thread = Thread::factory()->group()->create();
        $invite = Invite::factory()->for($thread)->owner($this->tippin)->expires(now()->addHour())->testing()->create();
        $this->travel(2)->hours();

        $this->getJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $invite->id,
                'thread_id' => $thread->id,
                'options' => [
                    'is_valid' => false,
                ],
            ]);
    }

    /** @test */
    public function guest_can_view_valid_invite()
    {
        $this->logCurrentRequest('GUEST');
        $thread = Thread::factory()->group()->create(['subject' => 'Group']);
        $invite = Invite::factory()->for($thread)->owner($this->tippin)->testing()->create();

        $this->getJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $invite->id,
                'thread_id' => $thread->id,
                'options' => [
                    'messenger_auth' => false,
                    'in_thread' => false,
                    'thread_name' => 'Group',
                    'is_valid' => true,
                ],
            ]);
    }

    /** @test */
    public function non_participant_can_view_valid_invite()
    {
        $this->logCurrentRequest('AUTHED');
        $thread = Thread::factory()->group()->create(['subject' => 'Group']);
        $invite = Invite::factory()->for($thread)->owner($this->tippin)->testing()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $invite->id,
                'thread_id' => $thread->id,
                'options' => [
                    'messenger_auth' => true,
                    'in_thread' => false,
                    'thread_name' => 'Group',
                    'is_valid' => true,
                ],
            ]);
    }

    /** @test */
    public function existing_participant_viewing_invite_shows_in_thread()
    {
        $thread = $this->createGroupThread($this->tippin);
        $invite = Invite::factory()->for($thread)->owner($this->tippin)->testing()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $invite->id,
                'thread_id' => $thread->id,
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
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create(['subject' => 'Group']);
        Invite::factory()->for($thread)->owner($this->tippin)->testing()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.invites.join.store', [
            'invite' => 'TEST1234',
        ]))
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $thread->id,
            ]);
    }

    /** @test */
    public function forbidden_to_join_group_with_valid_invite_when_disabled_from_group_settings()
    {
        $thread = Thread::factory()->group()->create(['invitations' => false]);
        Invite::factory()->for($thread)->owner($this->tippin)->testing()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.invites.join.store', [
            'invite' => 'TEST1234',
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_join_group_with_valid_invite_when_disabled_from_config()
    {
        $this->logCurrentRequest();
        Messenger::setThreadInvites(false);
        $thread = Thread::factory()->group()->create();
        Invite::factory()->for($thread)->owner($this->tippin)->testing()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.invites.join.store', [
            'invite' => 'TEST1234',
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function existing_participant_forbidden_to_join_group_with_valid_invite()
    {
        $thread = $this->createGroupThread($this->tippin);
        Invite::factory()->for($thread)->owner($this->tippin)->testing()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.invites.join.store', [
            'invite' => 'TEST1234',
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_join_group_with_expired_but_not_deleted_invite()
    {
        $thread = Thread::factory()->group()->create();
        Invite::factory()->for($thread)->owner($this->tippin)->expires(now()->addHour())->testing()->create();
        $this->travel(2)->hours();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.invites.join.store', [
            'invite' => 'TEST1234',
        ]))
            ->assertForbidden();
    }
}
