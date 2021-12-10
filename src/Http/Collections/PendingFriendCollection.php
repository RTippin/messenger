<?php

namespace RTippin\Messenger\Http\Collections;

use Illuminate\Http\Request;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Http\Collections\Base\MessengerCollection;
use RTippin\Messenger\Http\Resources\PendingFriendResource;
use RTippin\Messenger\Models\PendingFriend;
use Throwable;

class PendingFriendCollection extends MessengerCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->safeTransformer();
    }

    /**
     * @inheritDoc
     */
    protected function makeResource($resource): ?array
    {
        /** @var PendingFriend $friend */
        $friend = $resource;

        try {
            return $friend->sender instanceof MessengerProvider
                ? (new PendingFriendResource($friend))->resolve()
                : null;
        } catch (Throwable $t) {
            report($t);
        }

        return null;
    }
}
