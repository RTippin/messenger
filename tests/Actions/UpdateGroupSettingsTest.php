<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\UpdateGroupSettings;
use RTippin\Messenger\Broadcasting\ThreadSettingsBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ThreadSettingsEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\ThreadNameMessage;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateGroupSettingsTest extends FeatureTestCase
{
    private Thread $group;

    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->group = $this->createGroupThread($this->tippin);

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function update_group_settings_updates_thread()
    {
        app(UpdateGroupSettings::class)->withoutDispatches()->execute(
            $this->group,
            [
                'subject' => 'Rename Test Group',
                'add_participants' => false,
                'invitations' => false,
                'calling' => false,
                'messaging' => false,
                'knocks' => false,
            ]
        );

        $this->assertDatabaseHas('threads', [
            'subject' => 'Rename Test Group',
            'add_participants' => false,
            'invitations' => false,
            'calling' => false,
            'messaging' => false,
            'knocks' => false,
        ]);
    }

    /** @test */
    public function update_group_settings_name_fires_events()
    {
        Event::fake([
            ThreadSettingsBroadcast::class,
            ThreadSettingsEvent::class,
        ]);

        app(UpdateGroupSettings::class)->execute(
            $this->group,
            [
                'subject' => 'Rename Test Group',
            ]
        );

        Event::assertDispatched(function (ThreadSettingsBroadcast $event) {
            $this->assertContains('Rename Test Group', $event->broadcastWith());
            $this->assertContains('presence-thread.'.$this->group->id, $event->broadcastOn());

            return true;
        });

        Event::assertDispatched(function (ThreadSettingsEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->group->id, $event->thread->id);
            $this->assertTrue($event->nameChanged);

            return true;
        });
    }

    /** @test */
    public function update_group_settings_feature_fires_events()
    {
        Event::fake([
            ThreadSettingsBroadcast::class,
            ThreadSettingsEvent::class,
        ]);

        app(UpdateGroupSettings::class)->execute(
            $this->group,
            [
                'subject' => 'First Test Group',
                'add_participants' => true,
                'invitations' => true,
                'calling' => true,
                'messaging' => true,
                'knocks' => false,
            ]
        );

        Event::assertDispatched(function (ThreadSettingsBroadcast $event) {
            $this->assertContains('First Test Group', $event->broadcastWith());
            $this->assertContains('presence-thread.'.$this->group->id, $event->broadcastOn());

            return true;
        });

        Event::assertDispatched(function (ThreadSettingsEvent $event) {
            return $event->nameChanged === false;
        });
    }

    /** @test */
    public function update_group_settings_with_no_changes_fires_no_events()
    {
        $this->doesntExpectEvents([
            ThreadSettingsBroadcast::class,
            ThreadSettingsEvent::class,
        ]);

        app(UpdateGroupSettings::class)->execute(
            $this->group,
            [
                'subject' => 'First Test Group',
                'add_participants' => true,
                'invitations' => true,
                'calling' => true,
                'messaging' => true,
                'knocks' => true,
            ]
        );
    }

    /** @test */
    public function update_group_settings_triggers_listener()
    {
        Bus::fake();

        app(UpdateGroupSettings::class)->withoutBroadcast()->execute(
            $this->group,
            [
                'subject' => 'Rename Test Group',
            ]
        );

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === ThreadNameMessage::class;
        });
    }
}
