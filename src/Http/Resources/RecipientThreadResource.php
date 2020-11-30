<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;

class RecipientThreadResource extends JsonResource
{
    /**
     * @var MessengerProvider
     */
    private MessengerProvider $provider;

    /**
     * @var Thread|null
     */
    protected ?Thread $thread;

    /**
     * RecipientThreadResource constructor.
     * @param MessengerProvider $provider
     * @param Thread|null $thread
     */
    public function __construct(MessengerProvider $provider, Thread $thread = null)
    {
        parent::__Construct($provider);

        $this->thread = $thread;
        $this->provider = $provider;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public function toArray($request)
    {
        return [
            'recipient' => new ProviderResource($this->provider, true),
            'thread_id' => $this->thread
                ? $this->thread->id
                : null
        ];
    }
}
