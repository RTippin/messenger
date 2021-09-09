<?php

namespace RTippin\Messenger\Http\Resources\Broadcast;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Models\Friend;

class FriendApprovedBroadcastResource extends JsonResource
{
    /**
     * @var Friend
     */
    private Friend $friend;

    /**
     * FriendApprovedBroadcastResource constructor.
     *
     * @param  Friend  $friend
     */
    public function __construct(Friend $friend)
    {
        parent::__construct($friend);

        $this->friend = $friend;
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
            'sender' => (new ProviderResource($this->friend->party))->resolve(),
            $this->merge($this->friend->withoutRelations()->toArray()),
        ];
    }
}
