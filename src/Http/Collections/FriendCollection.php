<?php

namespace RTippin\Messenger\Http\Collections;

use Illuminate\Http\Request;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Http\Collections\Base\MessengerCollection;
use RTippin\Messenger\Http\Resources\FriendResource;
use RTippin\Messenger\Models\Friend;
use Throwable;

class FriendCollection extends MessengerCollection
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
        /** @var Friend $friend */
        $friend = $resource;

        try {
            return $friend->party instanceof MessengerProvider
                ? (new FriendResource($friend))->resolve()
                : null;
        } catch (Throwable $t) {
            report($t);
        }

        return null;
    }
}
