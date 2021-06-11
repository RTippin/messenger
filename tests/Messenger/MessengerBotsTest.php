<?php

namespace RTippin\Messenger\Tests\Messenger;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Facades\MessengerBots as BotsFacade;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Tests\MessengerTestCase;

class MessengerBotsTest extends MessengerTestCase
{
    private MessengerBots $bots;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bots = app(MessengerBots::class);
    }

    /** @test */
    public function messenger_bots_facade_same_instance_as_container()
    {
        $this->assertSame($this->bots, BotsFacade::getInstance());
    }

    /** @test */
    public function it_can_set_bot_actions()
    {
        $actions = [
            TestBot::class,
        ];

        $this->bots->setActions($actions);

        $this->assertSame($actions, $this->bots->getActions());
    }

    /** @test */
    public function it_ignores_invalid_and_missing_bot_actions()
    {
        $actions = [
            TestBot::class,
            InvalidBotAction::class,
            MissingAction::class,
        ];

        $this->bots->setActions($actions);

        $this->assertSame([TestBot::class], $this->bots->getActions());
    }
}

class TestBot extends BotActionHandler
{
    public function handle(): void
    {
        //
    }
}

class InvalidBotAction
{
    public function handle(): void
    {
        //
    }
}
