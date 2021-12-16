<?php

namespace RTippin\Messenger\Tests\DataTransferObjects;

use Illuminate\Support\Collection;
use RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO;
use RTippin\Messenger\DataTransferObjects\PackagedBotInstallDTO;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\MessengerTestCase;

class PackagedBotInstallDTOTest extends MessengerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([FunBotHandler::class]);
    }

    /** @test */
    public function it_sets_handler_dto()
    {
        $install = new PackagedBotInstallDTO(FunBotHandler::class, []);

        $this->assertInstanceOf(BotActionHandlerDTO::class, $install->handler);
        $this->assertSame('fun_bot', $install->handler->alias);
    }

    /** @test */
    public function it_sets_collection_with_defaults()
    {
        $install = new PackagedBotInstallDTO(FunBotHandler::class, []);
        $expects = [
            [
                'enabled' => true,
                'cooldown' => 30,
                'admin_only' => false,
            ],
        ];

        $this->assertInstanceOf(Collection::class, $install->data);
        $this->assertSame($expects, $install->data->toArray());
    }

    /** @test */
    public function it_sets_collection_with_overrides()
    {
        $install = new PackagedBotInstallDTO(FunBotHandler::class, [
            'enabled' => false,
            'cooldown' => 0,
            'admin_only' => true,
        ]);
        $expects = [
            [
                'enabled' => false,
                'cooldown' => 0,
                'admin_only' => true,
            ],
        ];

        $this->assertSame($expects, $install->data->toArray());
    }

    /** @test */
    public function it_sets_collection_with_extra_data()
    {
        $install = new PackagedBotInstallDTO(FunBotHandler::class, [
            'enabled' => false,
            'cooldown' => 0,
            'admin_only' => true,
        ]);
        $expects = [
            [
                'enabled' => false,
                'cooldown' => 0,
                'admin_only' => true,
            ],
        ];

        $this->assertSame($expects, $install->data->toArray());
    }

    /** @test */
    public function it_returns_array_using_only_handler_dto()
    {
        $install = new PackagedBotInstallDTO(FunBotHandler::class, []);

        $this->assertSame((new BotActionHandlerDTO(FunBotHandler::class))->toArray(), $install->toArray());
    }
}
