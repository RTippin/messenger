<?php

namespace RTippin\Messenger\Tests\Support;

use RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\Facades\MessengerBots as MessengerBotsFacade;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Tests\Fixtures\BrokenBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotPackage;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotPackage;
use RTippin\Messenger\Tests\MessengerTestCase;

class PackagedBotTest extends MessengerTestCase
{
    /** @test */
    public function it_has_settings()
    {
        $expects = [
            'alias' => 'fun_package',
            'name' => 'Fun Package',
            'description' => 'Fun package description.',
        ];

        $this->assertSame($expects, FunBotPackage::getSettings());
    }

    /** @test */
    public function it_has_installs()
    {
        $expects = [
            FunBotHandler::class => [
                'test' => ['one', 'two'],
                'special' => true,
            ],
            SillyBotHandler::class => [
                'triggers' => ['silly'],
                'match' => MessengerBots::MATCH_EXACT,
            ],
            BrokenBotHandler::class => [
                'triggers' => ['broken'],
                'match' => MessengerBots::MATCH_CONTAINS,
            ],
        ];

        $this->assertSame($expects, FunBotPackage::installs());
    }

    /** @test */
    public function it_doesnt_have_dto_if_not_registered()
    {
        $this->assertNull(FunBotPackage::getDTO());
    }

    /** @test */
    public function it_can_get_dto()
    {
        MessengerBotsFacade::registerPackagedBots([FunBotPackage::class]);
        $package = FunBotPackage::getDTO();

        $this->assertInstanceOf(PackagedBotDTO::class, $package);
        $this->assertSame('fun_package', $package->alias);
    }

    /** @test */
    public function it_registers_package_when_testing_installs()
    {
        $this->assertFalse(MessengerBotsFacade::isValidPackagedBot(FunBotPackage::class));

        FunBotPackage::testInstalls();

        $this->assertTrue(MessengerBotsFacade::isValidPackagedBot(FunBotPackage::class));
    }

    /** @test */
    public function it_can_test_installs_with_no_failures()
    {
        SillyBotPackage::$installs = [
            FunBotHandler::class => [
                'test' => ['one', 'two'],
                'special' => true,
            ],
        ];
        $passed = [
            [
                'handler' => (new BotActionHandlerDTO(FunBotHandler::class))->toArray(),
                'match' => MessengerBots::MATCH_EXACT_CASELESS,
                'triggers' => '!test|!more',
                'admin_only' => false,
                'cooldown' => 30,
                'enabled' => true,
                'payload' => '{"special":true,"test":["one","two"]}',
            ],
        ];

        $results = SillyBotPackage::testInstalls();

        $this->assertSame($passed, $results['resolved']->toArray());
        $this->assertCount(0, $results['failed']);
    }

    /** @test */
    public function it_can_test_installs_with_failures()
    {
        SillyBotPackage::$installs = [
            SillyBotHandler::class => [
                'triggers' => ['silly'],
                'match' => MessengerBots::MATCH_EXACT,
            ],
            BrokenBotHandler::class => [
                'triggers' => null,
                'match' => null,
                'cooldown' => -1,
            ],
        ];
        $passed = [
            [
                'handler' => (new BotActionHandlerDTO(SillyBotHandler::class))->toArray(),
                'match' => MessengerBots::MATCH_EXACT,
                'triggers' => 'silly',
                'admin_only' => false,
                'cooldown' => 30,
                'enabled' => true,
                'payload' => null,
            ],
        ];
        $failed = [
            [
                'handler' => BrokenBotHandler::class,
                'data' => [
                    'enabled' => true,
                    'cooldown' => -1,
                    'admin_only' => false,
                    'triggers' => null,
                    'match' => null,
                ],
                'errors' => [
                    'cooldown' => ['The cooldown must be between 0 and 900.'],
                    'match' => ['The match field is required.'],
                    'triggers' => ['The triggers field is required.'],
                ],
            ],
        ];
        $results = SillyBotPackage::testInstalls();

        $this->assertSame($passed, $results['resolved']->toArray());
        $this->assertSame($failed, $results['failed']->toArray());
    }
}
