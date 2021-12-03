<?php

namespace RTippin\Messenger\Tests\Services;

use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Services\PackagedBotResolverService;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\FunBotPackage;

class PackagedBotResolverServiceTest extends FeatureTestCase
{
    private PackagedBotResolverService $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(PackagedBotResolverService::class);
    }

    /** @test */
    public function it_tests()
    {
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(FunBotPackage::class);

        $results = $this->resolver->resolve($thread, $package);

        $this->assertTrue(true);
    }
}
