<?php

namespace RTippin\Messenger\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RTippin\Messenger\Exceptions\InvalidMessengerProvider;
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
     * @param Messenger $service
     */
    public function __construct(Messenger $service)
    {
        $this->messenger = $service;
    }

    /**
     * Handle an incoming request.
     * Perform your logic here to determine which model you
     * want to be used throughout the application,
     * as this messenger supports multiple models
     *
     * @param Request $request
     * @param Closure $next
     * @param null $required
     * @return mixed
     * @throws InvalidMessengerProvider
     */
    public function handle(Request $request, Closure $next, $required = null)
    {
        $this->setProvider($request);

        if($required === 'required' && ! $this->messenger->isProviderSet())
        {
            $this->messenger->throwProviderError();
        }

        return $next($request);
    }

    /**
     * Set the provider! You may override this however
     * you need to grab the model that is authed
     *
     * @param Request $request
     * @throws InvalidMessengerProvider
     */
    protected function setProvider(Request $request): void
    {
        if($request->user())
        {
            $this->messenger->setProvider(
                $request->user()
            );
        }
    }
}
