<?php

namespace RTippin\Messenger\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessengerApi
{
    /**
     * Middleware applied to our API / DATA routes.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Disable the outer-most 'data' key wrap
        // for our json resources / collections
        JsonResource::withoutWrapping();

        // We want to force the headers to JSON for our API as some
        // controllers return arrays, and we expect laravel's
        // response factory to transform to json
        if (! $request->headers->has('Accept')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
