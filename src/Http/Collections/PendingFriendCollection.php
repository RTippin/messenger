<?php

namespace RTippin\Messenger\Http\Collections;

use Exception;
use Illuminate\Http\Request;
use RTippin\Messenger\Http\Collections\Base\MessengerCollection;
use RTippin\Messenger\Http\Resources\PendingFriendResource;
use Throwable;

class PendingFriendCollection extends MessengerCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public function toArray($request): array
    {
        return $this->safeTransformer();
    }

    /**
     * @inheritDoc
     */
    protected function makeResource($pending): ?array
    {
        try {
            return (new PendingFriendResource($pending))->resolve();
        } catch (Exception $e) {
            report($e);
        } catch (Throwable $t) {
            report($t);
        }

        return null;
    }
}
