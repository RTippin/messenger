<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Bots\InstallPackagedBot;
use RTippin\Messenger\Events\PackagedBotInstalledEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Jobs\BotInstalledMessage;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\BrokenBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotPackage;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotPackage;

class InstallPackagedBotTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_bots_disabled()
    {
        Messenger::setBots(false);
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = FunBotPackage::getDTO();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bots are currently disabled.');

        app(InstallPackagedBot::class)->execute($thread, $package);
    }

    /** @test */
    public function it_installs_bot()
    {
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = FunBotPackage::getDTO();

        app(InstallPackagedBot::class)->execute($thread, $package);

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
        $package = SillyBotPackage::getDTO();

        app(InstallPackagedBot::class)->execute($thread, $package);

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
        SillyBotHandler::$authorized = true;
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = FunBotPackage::getDTO();

        app(InstallPackagedBot::class)->execute($thread, $package);

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
    public function it_ignores_unauthorized_handlers()
    {
        SillyBotHandler::$authorized = false;
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = FunBotPackage::getDTO();

        app(InstallPackagedBot::class)->execute($thread, $package);

        $this->assertDatabaseCount('bot_actions', 2);
        $this->assertDatabaseMissing('bot_actions', [
            'handler' => SillyBotHandler::class,
        ]);
    }

    /** @test */
    public function it_installs_bot_with_no_actions()
    {
        SillyBotPackage::$installs = [];
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = SillyBotPackage::getDTO();

        app(InstallPackagedBot::class)->execute($thread, $package);

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
        $package = SillyBotPackage::getDTO();
        $cache = Cache::spy();

        app(InstallPackagedBot::class)->execute($thread, $package);

        $cache->shouldHaveReceived('forget');
    }

    /** @test */
    public function it_installs_bot_avatar()
    {
        SillyBotPackage::$installs = [];
        SillyBotPackage::$avatar = __DIR__.'/../Fixtures/404.png';
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = SillyBotPackage::getDTO();

        $bot = app(InstallPackagedBot::class)->execute($thread, $package)->getBot();

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
        $package = SillyBotPackage::getDTO();

        $bot = app(InstallPackagedBot::class)->execute($thread, $package)->getBot();

        $this->assertNull($bot->avatar);
    }

    /** @test */
    public function it_fires_installed_event()
    {
        BaseMessengerAction::enableEvents();
        SillyBotPackage::$installs = [];
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = SillyBotPackage::getDTO();
        Event::fake([
            PackagedBotInstalledEvent::class,
        ]);

        app(InstallPackagedBot::class)->execute($thread, $package);

        Event::assertDispatched(function (PackagedBotInstalledEvent $event) use ($thread, $package) {
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
        $package = FunBotPackage::getDTO();

        app(InstallPackagedBot::class)->execute($thread, $package);

        Bus::assertDispatched(BotInstalledMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = FunBotPackage::getDTO();
        Messenger::setSystemMessageSubscriber('queued', false);

        app(InstallPackagedBot::class)->execute($thread, $package);

        Bus::assertDispatchedSync(BotInstalledMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = FunBotPackage::getDTO();
        Messenger::setSystemMessageSubscriber('enabled', false);

        app(InstallPackagedBot::class)->execute($thread, $package);

        Bus::assertNotDispatched(BotInstalledMessage::class);
    }
}
