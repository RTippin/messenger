<?php

namespace RTippin\Messenger\Tests\Services;

use RTippin\Messenger\Exceptions\ProviderNotFoundException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Services\ThreadLocatorService;
use RTippin\Messenger\Tests\FeatureTestCase;

class ThreadLocatorServiceTest extends FeatureTestCase
{
    private ThreadLocatorService $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = app(ThreadLocatorService::class);
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_returns_user_with_existing_thread_id()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $results = $this->locator->setAlias('user')->setId($this->doe->getKey())->locate();

        $this->assertSame($this->doe->getKey(), $results->getRecipient()->getKey());
        $this->assertSame($this->doe->getMorphClass(), $results->getRecipient()->getMorphClass());
        $this->assertSame($thread->id, $results->getThread()->id);
    }

    /** @test */
    public function it_returns_user_without_existing_thread_id()
    {
        $results = $this->locator->setAlias('user')->setId($this->doe->getKey())->locate();

        $this->assertSame($this->doe->getKey(), $results->getRecipient()->getKey());
        $this->assertSame($this->doe->getMorphClass(), $results->getRecipient()->getMorphClass());
        $this->assertNull($results->getThread());
    }

    /** @test */
    public function it_returns_company_with_existing_thread_id()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->developers);

        $results = $this->locator->setAlias('company')->setId($this->developers->getKey())->locate();

        $this->assertSame($this->developers->getKey(), $results->getRecipient()->getKey());
        $this->assertSame($this->developers->getMorphClass(), $results->getRecipient()->getMorphClass());
        $this->assertSame($thread->id, $results->getThread()->id);
    }

    /** @test */
    public function it_returns_company_without_existing_thread_id()
    {
        $results = $this->locator->setAlias('company')->setId($this->developers->getKey())->locate();

        $this->assertSame($this->developers->getKey(), $results->getRecipient()->getKey());
        $this->assertSame($this->developers->getMorphClass(), $results->getRecipient()->getMorphClass());
        $this->assertNull($results->getThread());
    }

    /** @test */
    public function it_returns_no_results_if_invalid_alias()
    {
        $results = $this->locator->setAlias('undefined')->setId($this->doe->getKey())->locate();

        $this->assertNull($results->getThread());
        $this->assertNull($results->getRecipient());
    }

    /** @test */
    public function it_returns_no_results_if_invalid_id()
    {
        $results = $this->locator->setAlias('user')->setId(404)->locate();

        $this->assertNull($results->getThread());
        $this->assertNull($results->getRecipient());
    }

    /** @test */
    public function it_returns_no_results_if_searching_for_current_provider()
    {
        $results = $this->locator->setAlias('user')->setId($this->tippin->getKey())->locate();

        $this->assertNull($results->getThread());
        $this->assertNull($results->getRecipient());
    }

    /** @test */
    public function it_can_throw_exception()
    {
        $this->expectException(ProviderNotFoundException::class);

        $this->locator->throwNotFoundError();
    }
}
