<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class InvitesTest extends HttpTestCase
{
    /** @test */
    public function forbidden_to_view_invites_on_private_thread()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_view_invites()
    {
        $thread = Thread::factory()->group()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_view_invites()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_with_permission_can_view_invites()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create(['manage_invites' => true]);
        Invite::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function admin_can_view_invites()
    {
        $thread = $this->createGroupThread($this->tippin);
        Invite::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function invalid_but_yet_to_be_deleted_invites_are_ignored_on_view()
    {
        $thread = $this->createGroupThread($this->tippin);
        Invite::factory()->for($thread)->owner($this->tippin)->invalid()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function invite_ignored_when_not_deleted_and_past_expires()
    {
        $thread = $this->createGroupThread($this->tippin);
        Invite::factory()->for($thread)->owner($this->tippin)->expires(now()->addHour())->create();
        $this->travel(2)->hours();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function admin_forbidden_to_view_invites_when_disabled_in_group_settings()
    {
        $thread = Thread::factory()->group()->create(['invitations' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_archive_invite()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        $invite = Invite::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.invites.destroy', [
            'thread' => $thread->id,
            'invite' => $invite->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_archive_invite()
    {
        $thread = $this->createGroupThread($this->tippin);
        $invite = Invite::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.invites.destroy', [
            'thread' => $thread->id,
            'invite' => $invite->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function participant_with_permission_can_archive_invite()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create(['manage_invites' => true]);
        $invite = Invite::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.invites.destroy', [
            'thread' => $thread->id,
            'invite' => $invite->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_create_invite()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $thread->id,
        ]), [
            'expires' => 0,
            'uses' => 25,
        ])
            ->assertSuccessful()
            ->assertJson([
                'max_use' => 25,
                'expires_at' => null,
            ]);
    }

    /** @test */
    public function participant_with_permission_can_create_invite()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create(['manage_invites' => true]);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $thread->id,
        ]), [
            'expires' => 5,
            'uses' => 50,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function forbidden_to_create_more_invites_than_the_max_allowed_from_config()
    {
        Messenger::setThreadInvitesMaxCount(1);
        $thread = $this->createGroupThread($this->tippin);
        Invite::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $thread->id,
        ]), [
            'expires' => 0,
            'uses' => 25,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_create_invites_when_disabled_from_config()
    {
        Messenger::setThreadInvites(false);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $thread->id,
        ]), [
            'expires' => 0,
            'uses' => 25,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_create_invites_when_disabled_in_group_settings()
    {
        $thread = Thread::factory()->group()->create(['invitations' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $thread->id,
        ]), [
            'expires' => 0,
            'uses' => 25,
        ])
            ->assertForbidden();
    }

    /**
     * @test
     * @dataProvider inviteValidation
     * @param $expiresValue
     * @param $usesValue
     * @param $errors
     */
    public function create_invite_fails_validation($expiresValue, $usesValue, $errors)
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $thread->id,
        ]), [
            'expires' => $expiresValue,
            'uses' => $usesValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors($errors);
    }

    public function inviteValidation(): array
    {
        return [
            'Fields cannot be empty' => ['', '', ['expires', 'uses']],
            'Fields cannot be false' => [false, false, ['expires', 'uses']],
            'Fields cannot be null' => [null, null, ['expires', 'uses']],
            'Fields must be 0 or greater' => [-1, -1, ['expires', 'uses']],
            'Fields cannot be an array' => [[1, 2], [1, 2], ['expires', 'uses']],
            'Expires cannot be greater than 8' => [9, 0, ['expires']],
            'Uses cannot be greater than 100' => [5, 101, ['uses']],
        ];
    }
}
