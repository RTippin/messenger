<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\UpdateGroupSettings;
use RTippin\Messenger\Broadcasting\ThreadSettingsBroadcast;
use RTippin\Messenger\Events\ThreadSettingsEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\ThreadNameMessage;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateGroupSettingsTest extends FeatureTestCase
{
    use BroadcastLogger;

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
            'chat_bots' => false,
            'messaging' => false,
            'knocks' => false,
        ]);

        $this->assertDatabaseHas('threads', [
            'id' => $thread->id,
            'subject' => 'Rename Test Group',
            'add_participants' => false,
            'invitations' => false,
            'calling' => false,
            'chat_bots' => false,
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
        $this->logBroadcast(ThreadSettingsBroadcast::class);
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
            'chat_bots' => false,
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
            'chat_bots' => true,
            'messaging' => true,
            'knocks' => true,
        ]);

        Event::assertNotDispatched(ThreadSettingsBroadcast::class);
        Event::assertNotDispatched(ThreadSettingsEvent::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_name_did_not_change()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);

        app(UpdateGroupSettings::class)->execute($thread, [
            'subject' => 'Test',
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'chat_bots' => false,
            'messaging' => false,
            'knocks' => false,
        ]);

        Bus::assertNotDispatched(ThreadNameMessage::class);
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);

        app(UpdateGroupSettings::class)->execute($thread, [
            'subject' => 'Rename Test Group',
        ]);

        Bus::assertDispatched(ThreadNameMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('queued', false);
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);

        app(UpdateGroupSettings::class)->execute($thread, [
            'subject' => 'Rename Test Group',
        ]);

        Bus::assertDispatchedSync(ThreadNameMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('enabled', false);
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);

        app(UpdateGroupSettings::class)->execute($thread, [
            'subject' => 'Rename Test Group',
        ]);

        Bus::assertNotDispatched(ThreadNameMessage::class);
    }
}
