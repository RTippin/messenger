<?php

namespace RTippin\Messenger\Tests\Actions;

use Exception;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Bots\InstallPackagedBot;
use RTippin\Messenger\Events\PackagedBotInstalledEvent;
use RTippin\Messenger\Events\PackagedBotInstallFailedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Jobs\BotInstalledMessage;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\BrokenBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotPackage;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotPackage;

class InstallPackagedBotTest extends FeatureTestCase
{
    /** @test */
    public function it_throws_exception_if_bots_disabled()
    {
        Messenger::setBots(false);
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(FunBotPackage::class);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bots are currently disabled.');

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);
    }

    /** @test */
    public function it_installs_bot()
    {
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(FunBotPackage::class);

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);

        $this->assertDatabaseHas('bots', [
            'name' => 'Fun Package',
            'avatar' => null,
            'cooldown' => 0,
            'hide_actions' => false,
            'enabled' => true,
        ]);
    }

    /** @test */
    public function it_installs_bot_using_defined_parameters()
    {
        SillyBotPackage::$cooldown = 30;
        SillyBotPackage::$hideActions = true;
        SillyBotPackage::$enabled = false;
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(SillyBotPackage::class);

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);

        $this->assertDatabaseHas('bots', [
            'name' => 'Silly Package',
            'avatar' => null,
            'enabled' => false,
            'cooldown' => 30,
            'hide_actions' => true,
        ]);
    }

    /** @test */
    public function it_installs_actions()
    {
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(FunBotPackage::class);

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);

        $this->assertDatabaseCount('bot_actions', 3);
        $this->assertDatabaseHas('bot_actions', [
            'handler' => FunBotHandler::class,
        ]);
        $this->assertDatabaseHas('bot_actions', [
            'handler' => SillyBotHandler::class,
        ]);
        $this->assertDatabaseHas('bot_actions', [
            'handler' => BrokenBotHandler::class,
        ]);
    }

    /** @test */
    public function it_installs_bot_with_no_actions()
    {
        SillyBotPackage::$installs = [];
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(SillyBotPackage::class);

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);

        $this->assertDatabaseHas('bots', [
            'name' => 'Silly Package',
            'avatar' => null,
        ]);
        $this->assertDatabaseCount('bot_actions', 0);
    }

    /** @test */
    public function it_clears_bot_actions_cache()
    {
        SillyBotPackage::$installs = [];
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(SillyBotPackage::class);
        $cache = Cache::spy();

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);

        $cache->shouldHaveReceived('forget');
    }

    /** @test */
    public function it_clears_package_installing_cache()
    {
        SillyBotPackage::$installs = [];
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(SillyBotPackage::class);
        $cache = Cache::spy();

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);

        $cache->shouldHaveReceived('forget', ["packaged:bot:installing:$thread->id:$package->alias"]);
    }

    /** @test */
    public function it_installs_bot_avatar()
    {
        SillyBotPackage::$installs = [];
        SillyBotPackage::$avatar = __DIR__.'/../Fixtures/404.png';
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(SillyBotPackage::class);

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);

        $bot = Bot::first();

        $this->assertNotNull($bot->avatar);
        Storage::disk('messenger')->assertExists($bot->getAvatarPath());
    }

    /** @test */
    public function it_doesnt_install_bot_avatar_if_disabled()
    {
        Messenger::setBotAvatars(false);
        SillyBotPackage::$installs = [];
        SillyBotPackage::$avatar = __DIR__.'/../Fixtures/404.png';
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(SillyBotPackage::class);

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);

        $bot = Bot::first();

        $this->assertNull($bot->avatar);
    }

    /** @test */
    public function it_fires_installed_event()
    {
        BaseMessengerAction::enableEvents();
        SillyBotPackage::$installs = [];
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(SillyBotPackage::class);
        Event::fake([
            PackagedBotInstalledEvent::class,
        ]);

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);

        Event::assertDispatched(function (PackagedBotInstalledEvent $event) use ($thread, $package) {
            $this->assertSame($package, $event->packagedBot);
            $this->assertSame($thread->id, $event->thread->id);
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());

            return true;
        });
    }

    /** @test */
    public function it_fires_install_failed_event()
    {
        BaseMessengerAction::enableEvents();
        SillyBotPackage::$installs = [];
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(SillyBotPackage::class);
        Event::fake([
            PackagedBotInstallFailedEvent::class,
        ]);
        DB::shouldReceive('transaction')->andThrow(new Exception('Install Failed.'));

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);

        Event::assertDispatched(function (PackagedBotInstallFailedEvent $event) use ($thread, $package) {
            $this->assertSame('Install Failed.', $event->exception->getMessage());
            $this->assertSame($package, $event->packagedBot);
            $this->assertSame($thread->id, $event->thread->id);
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());

            return true;
        });
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(FunBotPackage::class);

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);

        Bus::assertDispatched(BotInstalledMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(FunBotPackage::class);
        Messenger::setSystemMessageSubscriber('queued', false);

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);

        Bus::assertDispatchedSync(BotInstalledMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(FunBotPackage::class);
        Messenger::setSystemMessageSubscriber('enabled', false);

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);

        Bus::assertNotDispatched(BotInstalledMessage::class);
    }
}
