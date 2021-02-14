<?php

namespace RTippin\Messenger\Tests\Middleware;

use Illuminate\Http\Request;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Middleware\SetMessengerProvider;
use RTippin\Messenger\Tests\Fixtures\OtherModel;
use RTippin\Messenger\Tests\MessengerTestCase;

class SetMessengerProviderTest extends MessengerTestCase
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
        $this->expectException(InvalidProviderException::class);

        $middleware = app(SetMessengerProvider::class);

        $request = new Request;

        $middleware->handle($request, function ($request) {
            $this->assertFalse(Messenger::isProviderSet());
        }, 'required');
    }

    /** @test */
    public function invalid_provider_throws_error()
    {
        $this->expectException(InvalidProviderException::class);

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

        $user = $this->getModelUser();

        $tippin = new $user([
            'first' => 'Richard',
            'last' => 'Tippin',
            'email' => 'tippin@example.net',
        ]);

        $request->setUserResolver(function () use ($tippin) {
            return $tippin;
        });

        $middleware = app(SetMessengerProvider::class);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('tippin@example.net', $request->user()->email);
            $this->assertTrue(Messenger::isProviderSet());
            $this->assertSame('tippin@example.net', Messenger::getProvider()->email);
        }, 'required');
    }

    /** @test */
    public function valid_company_provider_was_set()
    {
        $request = new Request;

        $company = $this->getModelCompany();

        $developers = new $company([
            'company_name' => 'Developers',
            'company_email' => 'developers@example.net',
        ]);

        $request->setUserResolver(function () use ($developers) {
            return $developers;
        });

        $middleware = app(SetMessengerProvider::class);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('developers@example.net', $request->user()->company_email);
            $this->assertTrue(Messenger::isProviderSet());
            $this->assertSame('developers@example.net', Messenger::getProvider()->company_email);
        }, 'required');
    }
}
