<?php

namespace RTippin\Messenger\Tests\Services;

use RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Services\PackagedBotResolverService;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\BrokenBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotPackage;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotPackage;

class PackagedBotResolverServiceTest extends FeatureTestCase
{
    /** @test */
    public function it_returns_valid_resolved_handlers()
    {
        SillyBotHandler::$authorized = true;
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = FunBotPackage::getDTO();
        $expects = [
            [
                'handler' => FunBotHandler::getDTO()->toArray(),
                'match' => 'exact:caseless',
                'triggers' => '!test|!more',
                'admin_only' => false,
                'cooldown' => 30,
                'enabled' => true,
                'payload' => '{"special":true,"test":["one","two"]}',
            ],
            [
                'handler' => SillyBotHandler::getDTO()->toArray(),
                'match' => 'exact',
                'triggers' => 'silly',
                'admin_only' => false,
                'cooldown' => 30,
                'enabled' => true,
                'payload' => null,
            ],
            [
                'handler' => BrokenBotHandler::getDTO()->toArray(),
                'match' => 'contains',
                'triggers' => 'broken',
                'admin_only' => false,
                'cooldown' => 30,
                'enabled' => true,
                'payload' => null,
            ],
        ];

        $results = app(PackagedBotResolverService::class)->resolve($thread, $package);

        $this->assertSame($expects, $results->toArray());
    }

    /** @test */
    public function it_returns_resolved_handlers_using_testing_method()
    {
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $package = FunBotPackage::getDTO();
        $expects = [
            [
                'handler' => FunBotHandler::getDTO()->toArray(),
                'match' => 'exact:caseless',
                'triggers' => '!test|!more',
                'admin_only' => false,
                'cooldown' => 30,
                'enabled' => true,
                'payload' => '{"special":true,"test":["one","two"]}',
            ],
            [
                'handler' => SillyBotHandler::getDTO()->toArray(),
                'match' => 'exact',
                'triggers' => 'silly',
                'admin_only' => false,
                'cooldown' => 30,
                'enabled' => true,
                'payload' => null,
            ],
            [
                'handler' => BrokenBotHandler::getDTO()->toArray(),
                'match' => 'contains',
                'triggers' => 'broken',
                'admin_only' => false,
                'cooldown' => 30,
                'enabled' => true,
                'payload' => null,
            ],
        ];

        $results = app(PackagedBotResolverService::class)->resolveForTesting($package);

        $this->assertSame($expects, $results['resolved']->toArray());
        $this->assertSame([], $results['failed']->toArray());
    }

    /** @test */
    public function it_ignores_unauthorized_handlers()
    {
        SillyBotHandler::$authorized = false;
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = FunBotPackage::getDTO();
        $expects = [
            [
                'handler' => FunBotHandler::getDTO()->toArray(),
                'match' => 'exact:caseless',
                'triggers' => '!test|!more',
                'admin_only' => false,
                'cooldown' => 30,
                'enabled' => true,
                'payload' => '{"special":true,"test":["one","two"]}',
            ],
            [
                'handler' => BrokenBotHandler::getDTO()->toArray(),
                'match' => 'contains',
                'triggers' => 'broken',
                'admin_only' => false,
                'cooldown' => 30,
                'enabled' => true,
                'payload' => null,
            ],
        ];

        $results = app(PackagedBotResolverService::class)->resolve($thread, $package);

        $this->assertSame($expects, $results->toArray());
    }

    /** @test */
    public function it_ignores_unique_handlers_that_exists_in_the_thread()
    {
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = FunBotPackage::getDTO();
        BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )
            ->owner($this->tippin)
            ->handler(SillyBotHandler::class)
            ->triggers('!test')
            ->match('any')
            ->create();

        $results = app(PackagedBotResolverService::class)->resolve($thread, $package);

        $sillyExists = $results->search(
            fn (ResolvedBotHandlerDTO $handler) => $handler->handlerDTO->class === SillyBotHandler::class
        );

        $this->assertCount(2, $results);
        $this->assertFalse($sillyExists);
    }

    /** @test */
    public function it_returns_empty_collection_if_no_installs_defined()
    {
        SillyBotPackage::$installs = [];
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = SillyBotPackage::getDTO();

        $results = app(PackagedBotResolverService::class)->resolve($thread, $package);

        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_ignores_handlers_that_fail_validation()
    {
        SillyBotPackage::$installs = [
            FunBotHandler::class => [
                'test' => null,
                'special' => 404,
            ],
            BrokenBotHandler::class => [
                'triggers' => ['broken'],
                'match' => \RTippin\Messenger\MessengerBots::MATCH_CONTAINS,
            ],
        ];
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $package = SillyBotPackage::getDTO();
        $resolver = app(PackagedBotResolverService::class);
        $thread = $this->createGroupThread($this->tippin);
        $expects = [
            [
                'handler' => BrokenBotHandler::getDTO()->toArray(),
                'match' => 'contains',
                'triggers' => 'broken',
                'admin_only' => false,
                'cooldown' => 30,
                'enabled' => true,
                'payload' => null,
            ],
        ];

        $results = $resolver->resolve($thread, $package);

        $this->assertSame($expects, $results->toArray());
    }

    /** @test */
    public function it_returns_multiple_of_the_same_handler_if_defined_using_multiple_data_arrays()
    {
        SillyBotPackage::$installs = [
            FunBotHandler::class => [
                [
                    'test' => ['one', 'two'],
                    'special' => true,
                ],
                [
                    'test' => ['three', 'four'],
                    'special' => false,
                ],
            ],
        ];
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = SillyBotPackage::getDTO();
        $expects = [
            [
                'handler' => FunBotHandler::getDTO()->toArray(),
                'match' => 'exact:caseless',
                'triggers' => '!test|!more',
                'admin_only' => false,
                'cooldown' => 30,
                'enabled' => true,
                'payload' => '{"special":true,"test":["one","two"]}',
            ],
            [
                'handler' => FunBotHandler::getDTO()->toArray(),
                'match' => 'exact:caseless',
                'triggers' => '!test|!more',
                'admin_only' => false,
                'cooldown' => 30,
                'enabled' => true,
                'payload' => '{"special":false,"test":["three","four"]}',
            ],
        ];

        $results = app(PackagedBotResolverService::class)->resolve($thread, $package);

        $this->assertSame($expects, $results->toArray());
    }

    /** @test */
    public function it_can_overwrite_default_parameters()
    {
        SillyBotHandler::$authorized = true;
        SillyBotPackage::$installs = [
            SillyBotHandler::class => [
                'match' => 'exact',
                'triggers' => ['silly'],
                'admin_only' => true,
                'cooldown' => 0,
                'enabled' => false,
            ],
        ];
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = SillyBotPackage::getDTO();
        $expects = [
            [
                'handler' => SillyBotHandler::getDTO()->toArray(),
                'match' => 'exact',
                'triggers' => 'silly',
                'admin_only' => true,
                'cooldown' => 0,
                'enabled' => false,
                'payload' => null,
            ],
        ];

        $results = app(PackagedBotResolverService::class)->resolve($thread, $package);

        $this->assertSame($expects, $results->toArray());
    }

    /** @test */
    public function it_uses_supplied_parameters_without_defaults()
    {
        SillyBotHandler::$authorized = true;
        SillyBotPackage::$installs = [
            SillyBotHandler::class => [
                'match' => 'exact',
                'triggers' => ['silly'],
            ],
        ];
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = SillyBotPackage::getDTO();
        $expects = [
            [
                'handler' => SillyBotHandler::getDTO()->toArray(),
                'match' => 'exact',
                'triggers' => 'silly',
                'admin_only' => false,
                'cooldown' => 30,
                'enabled' => true,
                'payload' => null,
            ],
        ];

        $results = app(PackagedBotResolverService::class)->resolve($thread, $package);

        $this->assertSame($expects, $results->toArray());
    }
}
