<?php

namespace RTippin\Messenger\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthenticateOptional extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string[] ...$guards
     * @return mixed
     * @noinspection PhpMissingParamTypeInspection
     */
    public function handle($request, Closure $next, ...$guards)
    {
        try{
            $this->authenticate($request, $guards);
        }catch (AuthenticationException $e){
            //Not authenticated, continue on
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param Request $request
     * @noinspection PhpMissingParamTypeInspection
     * @return RedirectResponse|void
     */
    protected function redirectTo($request)
    {
        if ( ! $request->expectsJson())
        {
            return redirect('/');
        }
    }
}
