<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Events\InstallPackagedBotEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Jobs\ProcessPackagedBotInstall;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Tests\Fixtures\FunBotPackage;
use RTippin\Messenger\Tests\Fixtures\SillyBotPackage;
use RTippin\Messenger\Tests\HttpTestCase;

class InstallBotPackageTest extends HttpTestCase
{
    /** @test */
    public function non_participant_forbidden_to_install_packaged_bot()
    {
        MessengerBots::registerPackagedBots([
            FunBotPackage::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.bots.packages.install', [
            'thread' => $thread->id,
        ]), [
            'alias' => 'fun_package',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_install_packaged_bot_if_authorization_fails()
    {
        $this->logCurrentRequest();
        SillyBotPackage::$authorized = false;
        MessengerBots::registerPackagedBots([
            SillyBotPackage::class,
        ]);
        Event::fake([
            InstallPackagedBotEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.packages.install', [
            'thread' => $thread->id,
        ]), [
            'alias' => 'silly_package',
        ])
            ->assertForbidden();

        Event::assertNotDispatched(InstallPackagedBotEvent::class);
    }

    /** @test */
    public function participant_with_permission_can_install_packaged_bot()
    {
        $this->logCurrentRequest();
        MessengerBots::registerPackagedBots([
            FunBotPackage::class,
        ]);
        Event::fake([
            InstallPackagedBotEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        Participant::factory()->for($thread)->owner($this->doe)->create(['manage_bots' => true]);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.bots.packages.install', [
            'thread' => $thread->id,
        ]), [
            'alias' => 'fun_package',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function install_packaged_bot_triggers_event()
    {
        MessengerBots::registerPackagedBots([
            FunBotPackage::class,
        ]);
        Event::fake([
            InstallPackagedBotEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(FunBotPackage::class);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.packages.install', [
            'thread' => $thread->id,
        ]), [
            'alias' => 'fun_package',
        ])
            ->assertSuccessful();

        Event::assertDispatched(function (InstallPackagedBotEvent $event) use ($thread, $package) {
            $this->assertSame($package, $event->package);
            $this->assertSame($thread->id, $event->thread->id);
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());

            return true;
        });
    }

    /** @test */
    public function install_packaged_bot_dispatches_subscriber_job()
    {
        MessengerBots::registerPackagedBots([
            FunBotPackage::class,
        ]);
        Bus::fake();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.packages.install', [
            'thread' => $thread->id,
        ]), [
            'alias' => 'fun_package',
        ]);

        Bus::assertDispatched(ProcessPackagedBotInstall::class);
    }

    /** @test */
    public function install_packaged_bot_runs_subscriber_job_now()
    {
        MessengerBots::registerPackagedBots([
            FunBotPackage::class,
        ]);
        Messenger::setBotSubscriber('queued', false);
        Bus::fake();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.packages.install', [
            'thread' => $thread->id,
        ]), [
            'alias' => 'fun_package',
        ]);

        Bus::assertDispatchedSync(ProcessPackagedBotInstall::class);
    }

    /** @test */
    public function install_packaged_bot_doesnt_dispatch_subscriber_job_if_disabled()
    {
        MessengerBots::registerPackagedBots([
            FunBotPackage::class,
        ]);
        Messenger::setBotSubscriber('enabled', false);
        Bus::fake();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.packages.install', [
            'thread' => $thread->id,
        ]), [
            'alias' => 'fun_package',
        ]);

        Bus::assertNotDispatched(ProcessPackagedBotInstall::class);
    }

    /**
     * @test
     * @dataProvider aliasFailsValidation
     *
     * @param $alias
     */
    public function install_packaged_bot_fails_alias_validation($alias)
    {
        $this->logCurrentRequest();
        MessengerBots::registerPackagedBots([
            FunBotPackage::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.packages.install', [
            'thread' => $thread->id,
        ]), [
            'alias' => $alias,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['alias']);
    }

    public function aliasFailsValidation(): array
    {
        return [
            'Alias is required' => [null],
            'Alias cannot be boolean' => [true],
            'Alias cannot be an integer' => [30],
            'Alias cannot be an array' => [[1, 2]],
            'Alias must be a valid registered alias' => ['unknown'],
        ];
    }
}
