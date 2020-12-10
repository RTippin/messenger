<?php

namespace RTippin\Messenger\Tests\Messenger;

use RTippin\Messenger\Tests\FeatureTestCase;

class RouteTest extends FeatureTestCase
{
    /** @test */
    public function test_role_function()
    {
        $this->assertTrue(app('router')->has('api.messenger.threads.show'));
    }
}
