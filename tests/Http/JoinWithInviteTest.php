<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Events\InviteUsedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class JoinWithInviteTest extends FeatureTestCase
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
        $this->actingAs($this->userDoe());

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
        Event::fake([
            InviteUsedEvent::class,
        ]);

        $smith = $this->createJaneSmith();

        $this->actingAs($smith);

        $this->postJson(route('api.messenger.invites.join', [
            'invite' => 'TEST1234',
        ]))
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->group->id,
            ]);

        $this->assertDatabaseHas('participants', [
            'thread_id' => $this->group->id,
            'owner_id' => $smith->getKey(),
            'owner_type' => get_class($smith),
        ]);

        $this->assertEquals(3, $this->group->participants()->count());

        Event::assertDispatched(function (InviteUsedEvent $event) use ($smith) {
            $this->assertEquals($smith->getKey(), $event->provider->getKey());
            $this->assertEquals($this->group->id, $event->thread->id);
            $this->assertEquals($this->invite->id, $event->invite->id);

            return true;
        });
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
        $this->actingAs($this->userDoe());

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
