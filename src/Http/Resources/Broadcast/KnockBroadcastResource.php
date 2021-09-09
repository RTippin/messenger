<?php

namespace RTippin\Messenger\Http\Resources\Broadcast;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Models\Thread;

class KnockBroadcastResource extends JsonResource
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
     * KnockBroadcastResource constructor.
     *
     * @param  MessengerProvider  $provider
     * @param  Thread  $thread
     */
    public function __construct(MessengerProvider $provider, Thread $thread)
    {
        parent::__construct($provider);

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
            'thread' => [
                'id' => $this->thread->id,
                'type' => $this->thread->type,
                'type_verbose' => $this->thread->getTypeVerbose(),
                'group' => $this->thread->isGroup(),
                $this->mergeWhen($this->thread->isGroup(), fn () => [
                    'name' => $this->thread->name(),
                    'avatar' => $this->thread->threadAvatar(),
                ]),
            ],
            'sender' => (new ProviderResource($this->provider))->resolve(),
        ];
    }
}
