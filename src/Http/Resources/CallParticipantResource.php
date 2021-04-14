<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Models\CallParticipant;

class CallParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var CallParticipant $participant */
        $participant = $this->resource;

        return [
            'id' => $participant->id,
            'call_id' => $participant->call_id,
            'owner_id' => $participant->owner_id,
            'owner_type' => $participant->owner_type,
            'owner' => (new ProviderResource($participant->owner, true))->resolve(),
            'created_at' => $participant->created_at,
            'updated_at' => $participant->updated_at,
            'left_call' => $participant->left_call,
        ];
    }
}
