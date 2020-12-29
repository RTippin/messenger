<?php

namespace RTippin\Messenger\Tests\Middleware;

use Illuminate\Http\Request;
use RTippin\Messenger\Exceptions\InvalidMessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Middleware\SetMessengerProvider;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\OtherModel;
use RTippin\Messenger\Tests\stubs\UserModel;

class SetMessengerProviderTest extends FeatureTestCase
{
    /** @test */
    public function test_guest_will_not_be_set()
    {
        $middleware = app(SetMessengerProvider::class);

        $request = new Request;

        $middleware->handle($request, function ($request) {
            $this->assertFalse(Messenger::isProviderSet());
        });
    }

    /** @test */
    public function test_required_provider_throws_error_when_none()
    {
        $this->expectException(InvalidMessengerProvider::class);

        $middleware = app(SetMessengerProvider::class);

        $request = new Request;

        $middleware->handle($request, function ($request) {
            $this->assertFalse(Messenger::isProviderSet());
        }, 'required');
    }

    /** @test */
    public function test_invalid_provider_throws_error()
    {
        $this->expectException(InvalidMessengerProvider::class);

        $request = new Request;

        $request->setUserResolver(function () {
            return new OtherModel;
        });

        $middleware = app(SetMessengerProvider::class);

        $middleware->handle($request, function ($request) {
            $this->assertFalse(Messenger::isProviderSet());
        }, 'required');
    }

    /** @test */
    public function test_valid_provider_was_set()
    {
        $request = new Request;

        $request->setUserResolver(function () {
            return UserModel::find(1);
        });

        $middleware = app(SetMessengerProvider::class);

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('richard.tippin@gmail.com', $request->user()->email);
            $this->assertTrue(Messenger::isProviderSet());
            $this->assertEquals('richard.tippin@gmail.com', Messenger::getProvider()->email);
        }, 'required');
    }
}
