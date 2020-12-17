<?php

namespace RTippin\Messenger\Tests\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Http\Middleware\MessengerApi;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessengerApiMiddlewareTest extends FeatureTestCase
{
    /** @test */
    public function test_wrapping_disabled_on_json_resource_and_json_header_is_set()
    {
        $middleware = app(MessengerApi::class);

        $request = new Request;

        $middleware->handle($request, function (Request $request) {
            $this->assertNull(JsonResource::$wrap);
            $this->assertTrue($request->hasHeader('Accept'));
            $this->assertEquals('application/json', $request->header('Accept'));
        });
    }

}