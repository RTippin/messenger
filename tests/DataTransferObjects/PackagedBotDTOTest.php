<?php

namespace RTippin\Messenger\Tests\DataTransferObjects;

use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\BrokenBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotPackage;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotPackage;

class PackagedBotDTOTest extends FeatureTestCase
{
    /** @test */
    public function it_registers_bot_handlers_defined()
    {
        $this->assertFalse(MessengerBots::isValidHandler(FunBotHandler::class));
        $this->assertFalse(MessengerBots::isValidHandler(SillyBotHandler::class));
        $this->assertFalse(MessengerBots::isValidHandler(BrokenBotHandler::class));

        new PackagedBotDTO(FunBotPackage::class);

        $this->assertTrue(MessengerBots::isValidHandler(FunBotHandler::class));
        $this->assertTrue(MessengerBots::isValidHandler(SillyBotHandler::class));
        $this->assertTrue(MessengerBots::isValidHandler(BrokenBotHandler::class));
    }

    /** @test */
    public function it_sets_bot_properties_and_defaults()
    {
        $package = new PackagedBotDTO(FunBotPackage::class);

        $this->assertSame(FunBotPackage::class, $package->class);
        $this->assertSame('fun_package', $package->alias);
        $this->assertSame('Fun Package', $package->name);
        $this->assertSame('Fun package description.', $package->description);
        $this->assertNull($package->avatar);
        $this->assertFalse($package->shouldInstallAvatar);
        $this->assertSame('png', $package->avatarExtension);
        $this->assertFalse($package->shouldAuthorize);
        $this->assertCount(3, $package->installs);
        $this->assertCount(0, $package->canInstall);
        $this->assertCount(0, $package->alreadyInstalled);

        // Defaults
        $this->assertSame(0, $package->cooldown);
        $this->assertTrue($package->isEnabled);
        $this->assertFalse($package->shouldHideActions);
    }

    /** @test */
    public function it_sets_bot_properties_using_overwrites()
    {
        $avatar = __DIR__.'/../Fixtures/404.png';
        SillyBotPackage::$enabled = false;
        SillyBotPackage::$avatar = $avatar;
        SillyBotPackage::$cooldown = 120;
        SillyBotPackage::$hideActions = true;
        SillyBotPackage::$installs = [];

        $package = new PackagedBotDTO(SillyBotPackage::class);

        $this->assertSame(SillyBotPackage::class, $package->class);
        $this->assertSame('silly_package', $package->alias);
        $this->assertSame('Silly Package', $package->name);
        $this->assertSame('Silly package description.', $package->description);
        $this->assertSame($avatar, $package->avatar);
        $this->assertTrue($package->shouldInstallAvatar);
        $this->assertSame('png', $package->avatarExtension);
        $this->assertTrue($package->shouldAuthorize);
        $this->assertCount(0, $package->installs);
        $this->assertCount(0, $package->canInstall);
        $this->assertCount(0, $package->alreadyInstalled);
        $this->assertSame(120, $package->cooldown);
        $this->assertFalse($package->isEnabled);
        $this->assertTrue($package->shouldHideActions);
    }

    /** @test */
    public function it_generates_installs()
    {
        $funPackage = new PackagedBotDTO(FunBotPackage::class);
        $sillyPackage = new PackagedBotDTO(SillyBotPackage::class);

        $this->assertSame('fun_bot', $funPackage->installs[0]->handler->alias);
        $this->assertSame('silly_bot', $funPackage->installs[1]->handler->alias);
        $this->assertSame('broken_bot', $funPackage->installs[2]->handler->alias);
        $this->assertSame('fun_bot', $sillyPackage->installs[0]->handler->alias);
        $this->assertSame('silly_bot', $sillyPackage->installs[1]->handler->alias);
    }

    /** @test */
    public function it_can_apply_filters_rejecting_unauthorized_handlers()
    {
        SillyBotHandler::$authorized = false;
        $funPackage = new PackagedBotDTO(FunBotPackage::class);
        $expects = [
            FunBotHandler::getDTO()->toArray(),
            BrokenBotHandler::getDTO()->toArray(),
        ];

        $funPackage->applyInstallFilters();

        $this->assertSame($expects, $funPackage->canInstall->toArray());
        $this->assertSame([], $funPackage->alreadyInstalled->toArray());
    }

    /** @test */
    public function it_can_apply_filters_rejecting_unique_handlers_already_in_thread()
    {
        SillyBotHandler::$authorized = true;
        $funPackage = new PackagedBotDTO(FunBotPackage::class);
        $thread = Thread::factory()->group()->create();
        $canInstall = [
            FunBotHandler::getDTO()->toArray(),
            BrokenBotHandler::getDTO()->toArray(),
        ];
        $alreadyInstalled = [
            SillyBotHandler::getDTO()->toArray(),
        ];
        BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )
            ->owner($this->tippin)
            ->handler(SillyBotHandler::class)
            ->create();

        $funPackage->applyInstallFilters($thread);

        $this->assertSame($canInstall, $funPackage->canInstall->toArray());
        $this->assertSame($alreadyInstalled, $funPackage->alreadyInstalled->toArray());
    }

    /** @test */
    public function it_returns_empty_arrays_without_filters_applied()
    {
        $funPackage = new PackagedBotDTO(FunBotPackage::class);

        $expects = [
            'alias' => 'fun_package',
            'name' => 'Fun Package',
            'description' => 'Fun package description.',
            'avatar' => [
                'sm' => '/messenger/assets/bot-package/sm/fun_package/avatar.png',
                'md' => '/messenger/assets/bot-package/md/fun_package/avatar.png',
                'lg' => '/messenger/assets/bot-package/lg/fun_package/avatar.png',
            ],
            'installs' => [],
            'already_installed' => [],
        ];

        $this->assertSame($expects, $funPackage->toArray());
    }

    /** @test */
    public function it_returns_array_applying_authorization_filter_without_thread_sorting_by_name()
    {
        SillyBotHandler::$authorized = false;
        $funPackage = new PackagedBotDTO(FunBotPackage::class);
        $expects = [
            'alias' => 'fun_package',
            'name' => 'Fun Package',
            'description' => 'Fun package description.',
            'avatar' => [
                'sm' => '/messenger/assets/bot-package/sm/fun_package/avatar.png',
                'md' => '/messenger/assets/bot-package/md/fun_package/avatar.png',
                'lg' => '/messenger/assets/bot-package/lg/fun_package/avatar.png',
            ],
            'installs' => [
                BrokenBotHandler::getDTO()->toArray(),
                FunBotHandler::getDTO()->toArray(),
            ],
            'already_installed' => [],
        ];

        $funPackage->applyInstallFilters();

        $this->assertSame($expects, $funPackage->toArray());
    }

    /** @test */
    public function it_returns_array_applying_filters_using_thead_to_reject_existing_uniques_sorting_by_name()
    {
        SillyBotHandler::$authorized = true;
        $funPackage = new PackagedBotDTO(FunBotPackage::class);
        $thread = Thread::factory()->group()->create();
        BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )
            ->owner($this->tippin)
            ->handler(SillyBotHandler::class)
            ->create();
        $expects = [
            'alias' => 'fun_package',
            'name' => 'Fun Package',
            'description' => 'Fun package description.',
            'avatar' => [
                'sm' => '/messenger/assets/bot-package/sm/fun_package/avatar.png',
                'md' => '/messenger/assets/bot-package/md/fun_package/avatar.png',
                'lg' => '/messenger/assets/bot-package/lg/fun_package/avatar.png',
            ],
            'installs' => [
                BrokenBotHandler::getDTO()->toArray(),
                FunBotHandler::getDTO()->toArray(),
            ],
            'already_installed' => [
                SillyBotHandler::getDTO()->toArray(),
            ],
        ];

        $funPackage->applyInstallFilters($thread);

        $this->assertSame($expects, $funPackage->toArray());
    }
}
