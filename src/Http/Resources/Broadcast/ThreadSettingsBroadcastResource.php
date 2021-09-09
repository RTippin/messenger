<?php

namespace RTippin\Messenger\Http\Resources\Broadcast;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Http\Resources\ThreadSettingsResource;
use RTippin\Messenger\Models\Thread;

class ThreadSettingsBroadcastResource extends JsonResource
{
    /**
     * @var MessengerProvider
     */
    private MessengerProvider $provider;

    /**
     * @var Thread
     */
    private Thread $thread;

    /**
     * ThreadSettingsBroadcastResource constructor.
     *
     * @param  MessengerProvider  $provider
     * @param  Thread  $thread
     */
    public function __construct(MessengerProvider $provider, Thread $thread)
    {
        parent::__construct($thread);

        $this->provider = $provider;
        $this->thread = $thread;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'sender' => (new ProviderResource($this->provider))->resolve(),
            $this->merge((new ThreadSettingsResource($this->thread))->resolve()),
        ];
    }
}
