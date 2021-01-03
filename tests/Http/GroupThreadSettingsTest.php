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

        $this->group = $this->createGroupThread(
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

        $this->getJson(route('api.messenger.threads.settings', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /**
     * @test
     * @dataProvider settingsValidation
     * @param $fieldValue
     */
    public function group_settings_checks_booleans($fieldValue)
    {
        $this->actingAs($this->userTippin());

        $this->putJson(route('api.messenger.threads.settings', [
            'thread' => $this->group->id,
        ]), [
            'subject' => 'Passing',
            'messaging' => $fieldValue,
            'add_participants' => $fieldValue,
            'invitations' => $fieldValue,
            'calling' => $fieldValue,
            'knocks' => $fieldValue,
        ])
            ->assertStatus(422)
            ->assertJsonMissingValidationErrors('subject')
            ->assertJsonValidationErrors([
                'add_participants',
                'invitations',
                'calling',
                'messaging',
                'knocks',
            ]);
    }

    /**
     * @test
     * @dataProvider subjectValidation
     * @param $subject
     */
    public function group_settings_checks_subject($subject)
    {
        $this->actingAs($this->userTippin());

        $this->putJson(route('api.messenger.threads.settings', [
            'thread' => $this->group->id,
        ]), [
            'subject' => $subject,
            'messaging' => true,
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'knocks' => true,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subject');
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

        Event::assertDispatched(ThreadSettingsBroadcast::class);

        Event::assertDispatched(function (ThreadSettingsEvent $event) {
            return $event->nameChanged === true;
        });
    }

    public function settingsValidation(): array
    {
        return [
            'Value cannot be an INT' => [2],
            'Value cannot be a string' => ['string'],
            'Value cannot be an array' => [[1, 2]],
            'Value cannot be null' => [null],
        ];
    }

    public function subjectValidation(): array
    {
        return [
            'Value cannot be an INT' => [2],
            'Value cannot be single character' => ['1'],
            'Value must be larger than 2 characters' => ['12'],
            'Value cannot be an array' => [[1, 2]],
            'Value cannot be null' => [null],
            'Value cannot be empty' => [''],
        ];
    }
}
