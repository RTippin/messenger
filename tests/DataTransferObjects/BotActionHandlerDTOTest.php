<?php

namespace RTippin\Messenger\Tests\DataTransferObjects;

use RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;
use RTippin\Messenger\Tests\MessengerTestCase;

class BotActionHandlerDTOTest extends MessengerTestCase
{
    /** @test */
    public function it_sets_handler_properties_using_overrides_and_authorize_not_defined()
    {
        $handler = new BotActionHandlerDTO(FunBotHandler::class);

        $this->assertSame(FunBotHandler::class, $handler->class);
        $this->assertSame('fun_bot', $handler->alias);
        $this->assertSame('Fun Bot', $handler->name);
        $this->assertSame('This is a fun bot.', $handler->description);
        $this->assertFalse($handler->unique);
        $this->assertFalse($handler->shouldAuthorize);
        $this->assertSame(['!test', '!more'], $handler->triggers);
        $this->assertSame(MessengerBots::MATCH_EXACT_CASELESS, $handler->matchMethod);
    }

    /** @test */
    public function it_sets_handler_properties_without_overrides_and_authorize_defined()
    {
        $handler = new BotActionHandlerDTO(SillyBotHandler::class);

        $this->assertSame(SillyBotHandler::class, $handler->class);
        $this->assertSame('silly_bot', $handler->alias);
        $this->assertSame('Silly Bot', $handler->name);
        $this->assertSame('This is a silly bot.', $handler->description);
        $this->assertTrue($handler->unique);
        $this->assertTrue($handler->shouldAuthorize);
        $this->assertNull($handler->triggers);
        $this->assertNull($handler->matchMethod);
    }

    /** @test */
    public function it_returns_array()
    {
        $handler = new BotActionHandlerDTO(FunBotHandler::class);
        $expects = [
            'alias' => 'fun_bot',
            'description' => 'This is a fun bot.',
            'name' => 'Fun Bot',
            'unique' => false,
            'authorize' => false,
            'triggers' => ['!test', '!more'],
            'match' => MessengerBots::MATCH_EXACT_CASELESS,
        ];

        $this->assertSame($expects, $handler->toArray());
    }

    /** @test */
    public function it_removes_trigger_overrides_if_match_override_is_match_any()
    {
        SillyBotHandler::$triggers = ['one', 'two'];
        SillyBotHandler::$match = MessengerBots::MATCH_ANY;
        $handler = new BotActionHandlerDTO(SillyBotHandler::class);

        $this->assertNull($handler->triggers);
    }
}
