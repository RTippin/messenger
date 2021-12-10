<?php

namespace RTippin\Messenger\Http\Collections;

use Illuminate\Http\Request;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Http\Collections\Base\MessengerCollection;
use RTippin\Messenger\Http\Resources\SentFriendResource;
use RTippin\Messenger\Models\SentFriend;
use Throwable;

class SentFriendCollection extends MessengerCollection
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
        /** @var SentFriend $friend */
        $friend = $resource;

        try {
            return $friend->recipient instanceof MessengerProvider
                ? (new SentFriendResource($friend))->resolve()
                : null;
        } catch (Throwable $t) {
            report($t);
        }

        return null;
    }
}
