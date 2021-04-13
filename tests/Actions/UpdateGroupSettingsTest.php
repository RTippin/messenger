<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\UpdateGroupSettings;
use RTippin\Messenger\Broadcasting\ThreadSettingsBroadcast;
use RTippin\Messenger\Events\ThreadSettingsEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\ThreadNameMessage;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateGroupSettingsTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin);
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_updates_thread_settings()
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
    public function it_fires_events_if_name_changed()
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
            $this->assertContains('presence-messenger.thread.'.$this->group->id, $event->broadcastOn());

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
    public function it_fires_events_if_updated()
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
            $this->assertContains('presence-messenger.thread.'.$this->group->id, $event->broadcastOn());

            return true;
        });
        Event::assertDispatched(function (ThreadSettingsEvent $event) {
            return $event->nameChanged === false;
        });
    }

    /** @test */
    public function it_doesnt_fire_events_if_not_updated()
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
    public function it_dispatches_listeners()
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
