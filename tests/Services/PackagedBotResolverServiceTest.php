<?php

namespace RTippin\Messenger\Tests\Services;

use RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Services\PackagedBotResolverService;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\FunBotPackage;
use RTippin\Messenger\Tests\Fixtures\SillyBotPackage;

class PackagedBotResolverServiceTest extends FeatureTestCase
{
    private PackagedBotResolverService $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(PackagedBotResolverService::class);
    }

    /** @test */
    public function it_returns_valid_resolved_handler_dtos()
    {
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(FunBotPackage::class);

        $results = $this->resolver->resolve($thread, $package);

        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $this->assertInstanceOf(ResolvedBotHandlerDTO::class, $result);
        }
    }

    /** @test */
    public function it_returns_empty_collection_if_no_installs_defined()
    {
        SillyBotPackage::$installs = [];
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $package = MessengerBots::getPackagedBots(SillyBotPackage::class);

        $results = $this->resolver->resolve($thread, $package);

        $this->assertCount(0, $results);
    }
}
