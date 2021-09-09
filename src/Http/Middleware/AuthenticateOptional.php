<?php

namespace RTippin\Messenger\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * @deprecated This middleware served no purpose and will be removed in v2.
 */
class AuthenticateOptional extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        try {
            $this->authenticate($request, $guards);
        } catch (AuthenticationException $e) {
            //Not authenticated, continue on
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  Request  $request
     * @return RedirectResponse|void
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return redirect('/');
        }
    }
}
