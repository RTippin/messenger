<?php

namespace RTippin\Messenger\Http\Resources\Broadcast;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Models\Call;

class CallBroadcastResource extends JsonResource
{
    /**
     * @var Call
     */
    private Call $call;

    /**
     * CallBroadcastResource constructor.
     *
     * @param  Call  $call
     */
    public function __construct(Call $call)
    {
        parent::__construct($call);

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
            'id' => $this->call->id,
            'type' => $this->call->type,
            'type_verbose' => $this->call->getTypeVerbose(),
            'thread_id' => $this->call->thread_id,
            'thread_type' => $this->call->thread->type,
            'created_at' => $this->call->created_at,
            'updated_at' => $this->call->updated_at,
        ];
    }
}
