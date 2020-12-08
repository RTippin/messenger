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
    protected Friend $friend;

    /**
     * FriendRequestBroadcastResource constructor.
     *
     * @param Friend $friend
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
     * @noinspection PhpMissingParamTypeInspection
     */
    public function toArray($request): array
    {
        return [
            'sender' => new ProviderResource($this->friend->party),
            $this->merge($this->friend->withoutRelations()),
        ];
    }
}
