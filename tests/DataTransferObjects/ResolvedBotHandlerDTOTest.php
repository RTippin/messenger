<?php

namespace RTippin\Messenger\Tests\DataTransferObjects;

use RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO;
use RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\MessengerTestCase;

class ResolvedBotHandlerDTOTest extends MessengerTestCase
{
    /** @test */
    public function it_sets_resolved_properties()
    {
        $handler = new BotActionHandlerDTO(FunBotHandler::class);
        $resolved = new ResolvedBotHandlerDTO(
            $handler,
            MessengerBots::MATCH_EXACT,
            true,
            false,
            30,
            'one|two',
            '{"test":true}'
        );

        $this->assertSame($handler, $resolved->handlerDTO);
        $this->assertSame(MessengerBots::MATCH_EXACT, $resolved->matchMethod);
        $this->assertTrue($resolved->enabled);
        $this->assertFalse($resolved->adminOnly);
        $this->assertSame(30, $resolved->cooldown);
        $this->assertSame('one|two', $resolved->triggers);
        $this->assertSame('{"test":true}', $resolved->payload);
    }

    /** @test */
    public function it_returns_array()
    {
        $handler = new BotActionHandlerDTO(FunBotHandler::class);
        $resolved = new ResolvedBotHandlerDTO(
            $handler,
            MessengerBots::MATCH_EXACT,
            false,
            true,
            30,
            null,
            null
        );
        $expects = [
            'handler' => $handler->toArray(),
            'match' => MessengerBots::MATCH_EXACT,
            'triggers' => null,
            'admin_only' => true,
            'cooldown' => 30,
            'enabled' => false,
            'payload' => null,
        ];

        $this->assertSame($expects, $resolved->toArray());
    }
}
