<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Models\SentFriend;

class SentFriendResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var SentFriend $friend */
        $friend = $this->resource;

        return [
            'recipient' => (new ProviderResource($friend->recipient, true, 2))->resolve(),
            'type_verbose' => 'SENT_FRIEND_REQUEST',
            $this->merge($friend->withoutRelations()->toArray()),
        ];
    }
}
