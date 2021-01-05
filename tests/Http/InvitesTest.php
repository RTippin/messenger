<?php

namespace RTippin\Messenger\Tests\Http;

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

        $this->group = $this->createGroupThread(
            $tippin,
            $this->userDoe()
        );

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
}
