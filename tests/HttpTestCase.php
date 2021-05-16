<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Routing\Middleware\ThrottleRequests;
use RTippin\Messenger\Actions\BaseMessengerAction;

class HttpTestCase extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        BaseMessengerAction::disableEvents();
        $this->withoutMiddleware(ThrottleRequests::class);
    }
}
