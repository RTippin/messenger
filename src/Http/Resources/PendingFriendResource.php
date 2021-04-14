<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Models\PendingFriend;

class PendingFriendResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var PendingFriend $friend */
        $friend = $this->resource;

        return [
            'sender' => (new ProviderResource($friend->sender, true, 3))->resolve(),
            'type_verbose' => 'PENDING_FRIEND_REQUEST',
            $this->merge($friend->withoutRelations()->toArray()),
        ];
    }
}
