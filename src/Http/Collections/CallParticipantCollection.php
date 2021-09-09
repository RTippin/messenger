<?php

namespace RTippin\Messenger\Http\Collections;

use Illuminate\Http\Request;
use RTippin\Messenger\Http\Collections\Base\MessengerCollection;
use RTippin\Messenger\Http\Resources\CallParticipantResource;
use Throwable;

class CallParticipantCollection extends MessengerCollection
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
        try {
            return (new CallParticipantResource($resource))->resolve();
        } catch (Throwable $t) {
            report($t);
        }

        return null;
    }
}
