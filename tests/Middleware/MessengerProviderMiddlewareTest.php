<?php

namespace RTippin\Messenger\Tests\Middleware;

use Illuminate\Http\Request;
use RTippin\Messenger\Exceptions\InvalidMessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Middleware\SetMessengerProvider;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\OtherModel;
use RTippin\Messenger\Tests\UserModel;

class MessengerProviderMiddlewareTest extends FeatureTestCase
{
    /** @test */
    public function test_guest_will_not_be_set_or_throw_provider_error()
    {
        $middleware = app(SetMessengerProvider::class);

        $request = new Request;

        $middleware->handle($request, function ($request) {
            $this->assertTrue(Messenger::isProviderSet() === false);
        });
    }

    /** @test */
    public function test_required_provider_throws_error_when_invalid_or_none()
    {
        $this->expectException(InvalidMessengerProvider::class);

        $middleware = app(SetMessengerProvider::class);

        $request = new Request;

        $middleware->handle($request, function ($request) {
            $this->assertTrue(Messenger::isProviderSet() === false);
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
            $this->assertTrue(Messenger::isProviderSet() === false);
        }, 'required');
    }

    /** @test */
    public function test_valid_provider_was_set()
    {
        $request = new Request;

        $request->setUserResolver(function () {
            return UserModel::first();
        });

        $middleware = app(SetMessengerProvider::class);

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('richard.tippin@gmail.com', $request->user()->email);
            $this->assertTrue(Messenger::isProviderSet() === true);
            $this->assertEquals('richard.tippin@gmail.com', Messenger::getProvider()->email);
        }, 'required');
    }

}