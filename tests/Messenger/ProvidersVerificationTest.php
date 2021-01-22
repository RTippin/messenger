<?php

namespace RTippin\Messenger\Tests\Messenger;

use Illuminate\Support\Collection;
use RTippin\Messenger\Tests\MessengerTestCase;
use RTippin\Messenger\Tests\stubs\ProvidersVerificationProxy;

class ProvidersVerificationTest extends MessengerTestCase
{
    private ProvidersVerificationProxy $verify;

    protected function setUp(): void
    {
        parent::setUp();

        $this->verify = new ProvidersVerificationProxy;
    }

    /** @test */
    public function empty_providers_returns_empty_collection()
    {
        $emptyResult = $this->verify->formatValidProviders([]);

        $this->assertInstanceOf(Collection::class, $emptyResult);

        $this->assertSame(0, $emptyResult->count());
    }

    /** @test */
    public function provider_passes_checks_and_returns_as_collection()
    {
        $user = $this->getUserProviderConfig();

        $result = $this->verify->collectAndFilterProviders($user);

        $this->assertInstanceOf(Collection::class, $result);

        $this->assertSame($user, $result->toArray());
    }
}
