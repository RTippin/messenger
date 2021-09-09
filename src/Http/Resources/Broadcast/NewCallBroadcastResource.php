<?php

namespace RTippin\Messenger\Http\Resources\Broadcast;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Models\Call;

class NewCallBroadcastResource extends JsonResource
{
    /**
     * @var MessengerProvider
     */
    private MessengerProvider $provider;

    /**
     * @var Call
     */
    private Call $call;

    /**
     * NewCallBroadcastResource constructor.
     *
     * @param  MessengerProvider  $provider
     * @param  Call  $call
     */
    public function __construct(MessengerProvider $provider, Call $call)
    {
        parent::__construct($provider);

        $this->provider = $provider;
        $this->call = $call;
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
            'call' => [
                'id' => $this->call->id,
                'type' => $this->call->type,
                'type_verbose' => $this->call->getTypeVerbose(),
                'thread_id' => $this->call->thread_id,
                'thread_type' => $this->call->thread->type,
                'created_at' => $this->call->created_at,
                'updated_at' => $this->call->updated_at,
                $this->mergeWhen($this->call->thread->isGroup(), fn () => [
                    'thread_name' => $this->call->thread->name(),
                    'thread_avatar' => $this->call->thread->threadAvatar(),
                ]),
            ],
            'sender' => (new ProviderResource($this->provider))->resolve(),
        ];
    }
}
