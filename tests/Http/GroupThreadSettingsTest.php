<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\ThreadSettingsBroadcast;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\ThreadSettingsEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\UserModel;

class GroupThreadSettingsTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupInitialGroup();
    }

    private function setupInitialGroup(): void
    {
        $this->group = Thread::create([
            'type' => 2,
            'subject' => 'First Test Group',
            'image' => '5.png',
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'knocks' => true,
        ]);

        $this->group->participants()
            ->create(array_merge(Definitions::DefaultAdminParticipant, [
                'owner_id' => 1,
                'owner_type' => self::UserModelType,
            ]));

        $this->group->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => 2,
                'owner_type' => self::UserModelType,
            ]));
    }

    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.threads.settings', [
            'thread' => $this->group->id,
        ]))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_view_group_settings()
    {
        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.threads.settings', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'name' => 'First Test Group',
            ]);
    }

    /** @test */
    public function non_admin_forbidden_to_view_group_settings()
    {
        $this->actingAs(UserModel::find(2));

        $this->getJson(route('api.messenger.threads.show', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();

        $this->getJson(route('api.messenger.threads.settings', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function group_settings_validates_request()
    {
        $this->actingAs(UserModel::find(1));

        $this->putJson(route('api.messenger.threads.settings', [
            'thread' => $this->group->id,
        ]), [
            'subject' => '12',
            'messaging' => 'nope',
        ])
            ->assertJsonValidationErrors([
                'subject',
                'add_participants',
                'invitations',
                'calling',
                'messaging',
                'knocks',
            ]);
    }

    /** @test */
    public function update_group_settings_without_changes_expects_no_events()
    {
        $this->doesntExpectEvents([
            ThreadSettingsBroadcast::class,
            ThreadSettingsEvent::class,
        ]);

        $this->actingAs(UserModel::find(1));

        $this->putJson(route('api.messenger.threads.settings', [
            'thread' => $this->group->id,
        ]), [
            'subject' => 'First Test Group',
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'messaging' => true,
            'knocks' => true,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function update_group_settings_expects_events_and_name_not_changed()
    {
        Event::fake([
            ThreadSettingsBroadcast::class,
            ThreadSettingsEvent::class,
        ]);

        $this->actingAs(UserModel::find(1));

        $this->putJson(route('api.messenger.threads.settings', [
            'thread' => $this->group->id,
        ]), [
            'subject' => 'First Test Group',
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'messaging' => false,
            'knocks' => false,
        ])
            ->assertSuccessful()
            ->assertJson([
                'name' => 'First Test Group',
                'messaging' => false,
                'knocks' => false,
            ]);

        Event::assertDispatched(function (ThreadSettingsBroadcast $event) {
            $this->assertContains('First Test Group', $event->broadcastWith());
            $this->assertContains('presence-thread.'.$this->group->id, $event->broadcastOn());

            return true;
        });

        Event::assertDispatched(function (ThreadSettingsEvent $event) {
            $this->assertEquals(1, $event->provider->getKey());
            $this->assertEquals($this->group->id, $event->thread->id);
            $this->assertFalse($event->nameChanged);

            return true;
        });
    }

    /** @test */
    public function update_group_settings_expects_events_and_name_did_change()
    {
        Event::fake([
            ThreadSettingsBroadcast::class,
            ThreadSettingsEvent::class,
        ]);

        $this->actingAs(UserModel::find(1));

        $this->putJson(route('api.messenger.threads.settings', [
            'thread' => $this->group->id,
        ]), [
            'subject' => 'Second Test Group',
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'messaging' => false,
            'knocks' => false,
        ])
            ->assertSuccessful()
            ->assertJson([
                'name' => 'Second Test Group',
                'messaging' => false,
                'knocks' => false,
            ]);

        Event::assertDispatched(function (ThreadSettingsBroadcast $event) {
            $this->assertContains('Second Test Group', $event->broadcastWith());
            $this->assertContains('presence-thread.'.$this->group->id, $event->broadcastOn());

            return true;
        });

        Event::assertDispatched(function (ThreadSettingsEvent $event) {
            $this->assertEquals(1, $event->provider->getKey());
            $this->assertEquals($this->group->id, $event->thread->id);
            $this->assertTrue($event->nameChanged);

            return true;
        });
    }
}
