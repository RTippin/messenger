<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Facades\Messenger;

class HttpTestCase extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        BaseMessengerAction::disableEvents();
        Storage::fake(Messenger::getThreadStorage('disk'));
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    protected function tearDown(): void
    {
        BaseMessengerAction::enableEvents();

        parent::tearDown();
    }
}
