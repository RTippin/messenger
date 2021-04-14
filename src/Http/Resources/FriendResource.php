<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Models\Friend;

class FriendResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Friend $friend */
        $friend = $this->resource;

        return [
            'party' => (new ProviderResource($friend->party, true, 1))->resolve(),
            'type_verbose' => 'FRIEND',
            $this->merge($friend->withoutRelations()->toArray()),
        ];
    }
}
