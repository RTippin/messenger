<?php

namespace RTippin\Messenger\Http\Resources\Broadcast;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Models\Thread;

class ThreadApprovalBroadcastResource extends JsonResource
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
    private bool $approved;

    /**
     * ThreadApprovalBroadcastResource constructor.
     *
     * @param  MessengerProvider  $provider
     * @param  Thread  $thread
     * @param  bool  $approved
     */
    public function __construct(MessengerProvider $provider,
                                Thread $thread,
                                bool $approved)
    {
        parent::__construct($provider);

        $this->provider = $provider;
        $this->thread = $thread;
        $this->approved = $approved;
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
                'approved' => $this->approved,
            ],
            'sender' => (new ProviderResource($this->provider))->resolve(),
        ];
    }
}
