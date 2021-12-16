<?php

namespace RTippin\Messenger\Tests\Middleware;

use Illuminate\Http\Request;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Middleware\SetMessengerProvider;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\OtherModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;
use RTippin\Messenger\Tests\MessengerTestCase;

class SetMessengerProviderTest extends MessengerTestCase
{
    /** @test */
    public function guest_will_not_be_set()
    {
        app(SetMessengerProvider::class)->handle(new Request, function ($request) {
            $this->assertFalse(Messenger::isProviderSet());
        });
    }

    /** @test */
    public function required_provider_throws_error_when_none()
    {
        $this->expectException(InvalidProviderException::class);

        app(SetMessengerProvider::class)->handle(new Request, fn ($request) => null, 'required');
    }

    /** @test */
    public function invalid_provider_throws_error()
    {
        $this->expectException(InvalidProviderException::class);

        $request = new Request;
        $request->setUserResolver(fn () => new OtherModel);

        app(SetMessengerProvider::class)->handle($request, fn ($request) => null, 'required');
    }

    /** @test */
    public function valid_user_provider_was_set()
    {
        $request = new Request;
        $tippin = UserModel::factory()->make([
            'first' => 'Richard',
            'last' => 'Tippin',
            'email' => 'tippin@example.net',
        ]);
        $request->setUserResolver(fn () => $tippin);

        app(SetMessengerProvider::class)->handle($request, function (Request $request) {
            $this->assertSame('tippin@example.net', $request->user()->email);
            $this->assertTrue(Messenger::isProviderSet());
            $this->assertSame('tippin@example.net', Messenger::getProvider()->email);
        }, 'required');
    }

    /** @test */
    public function valid_company_provider_was_set()
    {
        $request = new Request;
        $developers = CompanyModel::factory()->make([
            'company_name' => 'Developers',
            'company_email' => 'developers@example.net',
        ]);
        $request->setUserResolver(fn () => $developers);

        app(SetMessengerProvider::class)->handle($request, function (Request $request) {
            $this->assertSame('developers@example.net', $request->user()->company_email);
            $this->assertTrue(Messenger::isProviderSet());
            $this->assertSame('developers@example.net', Messenger::getProvider()->company_email);
        }, 'required');
    }
}
