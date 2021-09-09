<?php

namespace RTippin\Messenger\Http\Resources\Broadcast;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Models\SentFriend;

class FriendRequestBroadcastResource extends JsonResource
{
    /**
     * @var SentFriend
     */
    private SentFriend $friend;

    /**
     * FriendRequestBroadcastResource constructor.
     *
     * @param  SentFriend  $friend
     */
    public function __construct(SentFriend $friend)
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
            'sender' => (new ProviderResource($this->friend->sender))->resolve(),
            $this->merge($this->friend->withoutRelations()->toArray()),
        ];
    }
}
