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
        $this->logCurrentRequest();
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
        $this->logCurrentRequest();
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
        $this->logCurrentRequest();
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
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $invite = Invite::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.invites.destroy', [
            'thread' => $thread->id,
            'invite' => $invite->id,
        ]))
            ->assertStatus(204);
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
            ->assertStatus(204);
    }

    /** @test */
    public function admin_can_create_invite()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $thread->id,
        ]), [
            'uses' => 25,
            'expires' => null,
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
            'uses' => 25,
        ])
            ->assertForbidden();
    }

    /**
     * @test
     *
     * @dataProvider inviteValidationFailsUses
     *
     * @param  $usesValue
     */
    public function create_invite_fails_uses_validation($usesValue)
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $thread->id,
        ]), [
            'uses' => $usesValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('uses');
    }

    /**
     * @test
     *
     * @dataProvider inviteValidationFailsExpires
     *
     * @param  $expiresValue
     */
    public function create_invite_fails_expires_validation($expiresValue)
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $thread->id,
        ]), [
            'expires' => is_callable($expiresValue) ? $expiresValue() : $expiresValue,
            'uses' => 0,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('expires');
    }

    /**
     * @test
     *
     * @dataProvider inviteValidationPassesExpires
     *
     * @param  $expiresValue
     */
    public function create_invite_passes_expires_validation($expiresValue)
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $thread->id,
        ]), [
            'expires' => $expiresValue(),
            'uses' => 0,
        ])
            ->assertSuccessful();
    }

    public static function inviteValidationFailsUses(): array
    {
        return [
            'Uses cannot be empty' => [''],
            'Uses cannot be false' => [false],
            'Uses cannot be null' => [null],
            'Uses must be 0 or greater' => [-1],
            'Uses cannot be an array' => [[1, 2]],
            'Uses cannot be greater than 100' => [101],
        ];
    }

    public static function inviteValidationFailsExpires(): array
    {
        return [
            'Expires cannot be array' => [[0, 1]],
            'Expires cannot be false' => [false],
            'Expires cannot be integer' => [5],
            'Expires cannot be now' => [fn () => now()],
            'Expires cannot be before 5 minutes from now' => [fn () => now()->addMinutes(4)],
        ];
    }

    public static function inviteValidationPassesExpires(): array
    {
        return [
            'Expires can be 10 minutes from now' => [fn () => now()->addMinutes(10)],
            'Expires can be formatted year/month/day' => [fn () => now()->addWeek()->format('Y-m-d')],
            'Expires can be formatted day/month/year' => [fn () => now()->addWeek()->format('d-m-Y')],
            'Expires can be a year from now' => [fn () => now()->addYear()],
        ];
    }
}
