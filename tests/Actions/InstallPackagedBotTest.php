<?php

namespace RTippin\Messenger\Tests\Actions;

use RTippin\Messenger\Actions\Bots\InstallPackagedBot;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\FunBotPackage;

class InstallPackagedBotTest extends FeatureTestCase
{
    /** @test */
    public function it_tests()
    {
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(FunBotPackage::class);

        app(InstallPackagedBot::class)->execute($thread, $this->tippin, $package);

        $this->assertDatabaseHas('bots', [
            'name' => 'Fun Package',
        ]);
    }
}
