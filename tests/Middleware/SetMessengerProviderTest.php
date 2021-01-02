<?php

namespace RTippin\Messenger\Tests\Middleware;

use Illuminate\Http\Request;
use RTippin\Messenger\Exceptions\InvalidMessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Middleware\SetMessengerProvider;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\OtherModel;

class SetMessengerProviderTest extends FeatureTestCase
{
    /** @test */
    public function guest_will_not_be_set()
    {
        $middleware = app(SetMessengerProvider::class);

        $request = new Request;

        $middleware->handle($request, function ($request) {
            $this->assertFalse(Messenger::isProviderSet());
        });
    }

    /** @test */
    public function required_provider_throws_error_when_none()
    {
        $this->expectException(InvalidMessengerProvider::class);

        $middleware = app(SetMessengerProvider::class);

        $request = new Request;

        $middleware->handle($request, function ($request) {
            $this->assertFalse(Messenger::isProviderSet());
        }, 'required');
    }

    /** @test */
    public function invalid_provider_throws_error()
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
    public function valid_user_provider_was_set()
    {
        $request = new Request;

        $request->setUserResolver(function () {
            return $this->userTippin();
        });

        $middleware = app(SetMessengerProvider::class);

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('richard.tippin@gmail.com', $request->user()->email);
            $this->assertTrue(Messenger::isProviderSet());
            $this->assertEquals('richard.tippin@gmail.com', Messenger::getProvider()->email);
        }, 'required');
    }

    /** @test */
    public function valid_company_provider_was_set()
    {
        $request = new Request;

        $request->setUserResolver(function () {
            return $this->companyDevelopers();
        });

        $middleware = app(SetMessengerProvider::class);

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('developers@example.net', $request->user()->company_email);
            $this->assertTrue(Messenger::isProviderSet());
            $this->assertEquals('developers@example.net', Messenger::getProvider()->company_email);
        }, 'required');
    }
}
