<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\ThreadSettingsBroadcast;
use RTippin\Messenger\Events\ThreadSettingsEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class GroupThreadSettingsTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->makeGroupThread(
            $this->userTippin(),
            $this->userDoe()
        );
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
        $this->actingAs($this->userTippin());

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
        $this->actingAs($this->userDoe());

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
        $this->actingAs($this->userTippin());

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

        $this->actingAs($this->userTippin());

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
        $tippin = $this->userTippin();

        Event::fake([
            ThreadSettingsBroadcast::class,
            ThreadSettingsEvent::class,
        ]);

        $this->actingAs($tippin);

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

        Event::assertDispatched(function (ThreadSettingsEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals($this->group->id, $event->thread->id);
            $this->assertFalse($event->nameChanged);

            return true;
        });
    }

    /** @test */
    public function update_group_settings_expects_events_and_name_did_change()
    {
        $tippin = $this->userTippin();

        Event::fake([
            ThreadSettingsBroadcast::class,
            ThreadSettingsEvent::class,
        ]);

        $this->actingAs($tippin);

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

        Event::assertDispatched(function (ThreadSettingsEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals($this->group->id, $event->thread->id);
            $this->assertTrue($event->nameChanged);

            return true;
        });
    }
}
