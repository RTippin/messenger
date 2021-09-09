<?php

namespace RTippin\Messenger\Http\Resources\Broadcast;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Models\Thread;

class NewThreadBroadcastResource extends JsonResource
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
     * @var bool
     */
    private bool $pending;

    /**
     * NewThreadBroadcastResource constructor.
     *
     * @param  MessengerProvider  $provider
     * @param  Thread  $thread
     * @param  bool  $pending
     */
    public function __construct(MessengerProvider $provider,
                                Thread $thread,
                                bool $pending)
    {
        parent::__construct($provider);

        $this->provider = $provider;
        $this->thread = $thread;
        $this->pending = $pending;
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
                'pending' => $this->pending,
                'group' => $this->thread->isGroup(),
                'type' => $this->thread->type,
                'type_verbose' => $this->thread->getTypeVerbose(),
                'created_at' => $this->thread->created_at,
                $this->mergeWhen($this->thread->isGroup(), fn () => [
                    'name' => $this->thread->name(),
                    'avatar' => $this->thread->threadAvatar(),
                ]),
            ],
            'sender' => (new ProviderResource($this->provider))->resolve(),
        ];
    }
}
