<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\UpdateGroupSettings;
use RTippin\Messenger\Broadcasting\ThreadSettingsBroadcast;
use RTippin\Messenger\Events\ThreadSettingsEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\ThreadNameMessage;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateGroupSettingsTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_updates_thread_settings()
    {
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);

        app(UpdateGroupSettings::class)->execute($thread, [
            'subject' => 'Rename Test Group',
            'add_participants' => false,
            'invitations' => false,
            'calling' => false,
            'messaging' => false,
            'knocks' => false,
        ]);

        $this->assertDatabaseHas('threads', [
            'id' => $thread->id,
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
        BaseMessengerAction::enableEvents();
        Event::fake([
            ThreadSettingsBroadcast::class,
            ThreadSettingsEvent::class,
        ]);
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);

        app(UpdateGroupSettings::class)->execute($thread, [
            'subject' => 'Rename Test Group',
        ]);

        Event::assertDispatched(function (ThreadSettingsBroadcast $event) use ($thread) {
            $this->assertSame('Rename Test Group', $event->broadcastWith()['name']);
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());

            return true;
        });
        Event::assertDispatched(function (ThreadSettingsEvent $event) use ($thread) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);
            $this->assertTrue($event->nameChanged);

            return true;
        });
    }

    /** @test */
    public function it_fires_events_if_updated_without_name_change()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ThreadSettingsBroadcast::class,
            ThreadSettingsEvent::class,
        ]);
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);

        app(UpdateGroupSettings::class)->execute($thread, [
            'subject' => 'Test',
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'messaging' => false,
            'knocks' => false,
        ]);

        Event::assertDispatched(function (ThreadSettingsBroadcast $event) use ($thread) {
            $this->assertSame('Test', $event->broadcastWith()['name']);
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());

            return true;
        });
        Event::assertDispatched(function (ThreadSettingsEvent $event) {
            return $event->nameChanged === false;
        });
    }

    /** @test */
    public function it_doesnt_fire_events_if_not_updated()
    {
        BaseMessengerAction::enableEvents();
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);

        $this->doesntExpectEvents([
            ThreadSettingsBroadcast::class,
            ThreadSettingsEvent::class,
        ]);

        app(UpdateGroupSettings::class)->execute($thread, [
            'subject' => 'Test',
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'messaging' => true,
            'knocks' => true,
        ]);
    }

    /** @test */
    public function it_dispatches_listeners()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);

        app(UpdateGroupSettings::class)->execute($thread, [
            'subject' => 'Rename Test Group',
        ]);

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === ThreadNameMessage::class;
        });
    }
}
