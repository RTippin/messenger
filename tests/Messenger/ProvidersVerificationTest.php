<?php

namespace RTippin\Messenger\Tests\Messenger;

use RTippin\Messenger\ProvidersVerification;
use RTippin\Messenger\Tests\MessengerTestCase;

class ProvidersVerificationTest extends MessengerTestCase
{
    private ProvidersVerification $verify;

    protected function setUp(): void
    {
        parent::setUp();

        $this->verify = new ProvidersVerification;
    }

    /** @test */
    public function test()
    {

    }
}