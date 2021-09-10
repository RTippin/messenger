<?php

namespace RTippin\Messenger\Broadcasting\Channels;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;

class ThreadChannel
{
    use AuthorizesRequests;

    /**
     * @var Messenger
     */
    public Messenger $messenger;

    /**
     * Create a new channel instance.
     *
     * @param  Messenger  $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * Authenticate the provider's access to the channel.
     *
     * @param $user
     * @param  Thread  $thread
     * @return ProviderResource
     *
     * @throws AuthorizationException
     */
    public function join($user, Thread $thread): ProviderResource
    {
        $this->authorize('socket', $thread);

        return new ProviderResource(
            $this->messenger->getProvider(),
            false,
            null,
            false
        );
    }
}
