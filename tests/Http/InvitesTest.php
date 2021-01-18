<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Events\InviteArchivedEvent;
use RTippin\Messenger\Events\NewInviteEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class InvitesTest extends FeatureTestCase
{
    private Thread $group;

    private Invite $invite;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $this->group = $this->createGroupThread($tippin, $this->userDoe());

        $this->invite = $this->group->invites()
            ->create([
                'owner_id' => $tippin->getKey(),
                'owner_type' => get_class($tippin),
                'code' => 'TEST1234',
                'max_use' => 1,
                'uses' => 0,
                'expires_at' => now()->addHour(),
            ]);
    }

    /** @test */
    public function forbidden_to_view_invites_on_private_thread()
    {
        $tippin = $this->userTippin();

        $private = $this->createPrivateThread(
            $tippin,
            $this->userDoe()
        );

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_view_invites()
    {
        $this->actingAs($this->companyDevelopers());

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_view_invites()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_with_permission_can_view_invites()
    {
        $doe = $this->userDoe();

        $this->group->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first()
            ->update([
                'manage_invites' => true,
            ]);

        $this->actingAs($doe);

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function admin_can_view_invites()
    {
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $this->invite->id,
                        'code' => 'TEST1234',
                    ],
                ],
                'meta' => [
                    'total' => 1,
                    'max_allowed' => Messenger::getThreadMaxInvitesCount(),
                ],
            ]);
    }

    /** @test */
    public function invalid_but_yet_to_be_deleted_invites_are_ignored_on_view()
    {
        $this->invite->update([
            'uses' => 1,
        ]);

        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function invite_ignored_when_not_deleted_and_past_expires()
    {
        $this->travel(2)->hours();

        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function admin_forbidden_to_view_invites_when_disabled_in_group_settings()
    {
        $this->group->update([
            'invitations' => false,
        ]);

        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.invites.index', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_archive_invite()
    {
        $this->actingAs($this->userDoe());

        $this->deleteJson(route('api.messenger.threads.invites.destroy', [
            'thread' => $this->group->id,
            'invite' => $this->invite->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_archive_invite()
    {
        $this->expectsEvents([
            InviteArchivedEvent::class,
        ]);

        $this->actingAs($this->userTippin());

        $this->deleteJson(route('api.messenger.threads.invites.destroy', [
            'thread' => $this->group->id,
            'invite' => $this->invite->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function participant_with_permission_can_archive_invite()
    {
        $this->expectsEvents([
            InviteArchivedEvent::class,
        ]);

        $doe = $this->userDoe();

        $this->group->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first()
            ->update([
                'manage_invites' => true,
            ]);

        $this->actingAs($doe);

        $this->deleteJson(route('api.messenger.threads.invites.destroy', [
            'thread' => $this->group->id,
            'invite' => $this->invite->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_create_invite()
    {
        $this->expectsEvents([
            NewInviteEvent::class,
        ]);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $this->group->id,
        ]), [
            'expires' => 0,
            'uses' => 25,
        ])
            ->assertSuccessful()
            ->assertJson([
                'max_use' => 25,
                'expires_at' => null,
            ]);

        $this->assertDatabaseCount('thread_invites', 2);
    }

    /** @test */
    public function participant_with_permission_can_create_invite()
    {
        $this->expectsEvents([
            NewInviteEvent::class,
        ]);

        $doe = $this->userDoe();

        $this->group->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first()
            ->update([
                'manage_invites' => true,
            ]);

        $this->actingAs($doe);

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $this->group->id,
        ]), [
            'expires' => 5,
            'uses' => 50,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function user_forbidden_to_create_more_invites_than_the_max_allowed_from_config()
    {
        Messenger::setThreadInvitesMaxCount(1);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $this->group->id,
        ]), [
            'expires' => 0,
            'uses' => 25,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_forbidden_to_create_invites_when_disabled_from_config()
    {
        Messenger::setThreadInvites(false);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $this->group->id,
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
    public function create_invite_checks_values($expiresValue, $usesValue, $errors)
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.invites.store', [
            'thread' => $this->group->id,
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
