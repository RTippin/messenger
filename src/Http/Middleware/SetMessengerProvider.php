<?php

namespace RTippin\Messenger\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Messenger;

class SetMessengerProvider
{
    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * SetMessengerProvider constructor.
     *
     * @param  Messenger  $service
     */
    public function __construct(Messenger $service)
    {
        $this->messenger = $service;
    }

    /**
     * Attempt to set the current MessengerProvider for
     * this request cycle as the authenticated user.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  null  $required
     * @return mixed
     *
     * @throws InvalidProviderException
     */
    public function handle(Request $request, Closure $next, $required = null)
    {
        $this->setProvider($request);

        if ($required === 'required' && ! $this->messenger->isProviderSet()) {
            $this->messenger->throwProviderError();
        }

        return $next($request);
    }

    /**
     * Set the provider! You may override this method if you plan
     * to set your provider using different authentication
     * methods or guards.
     *
     * @param  Request  $request
     *
     * @throws InvalidProviderException
     */
    protected function setProvider(Request $request): void
    {
        if ($request->user()) {
            $this->messenger->setProvider(
                $request->user()
            );
        }
    }
}
