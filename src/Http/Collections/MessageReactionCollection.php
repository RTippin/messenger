<?php

namespace RTippin\Messenger\Http\Collections;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use RTippin\Messenger\Http\Resources\MessageReactionResource;
use RTippin\Messenger\Models\MessageReaction;
use Throwable;

class MessageReactionCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->condenseReactions(),
            'meta' => [
                'total' => $this->collection->count(),
                'total_unique' => $this->collection->uniqueStrict('reaction')->count(),
            ],
        ];
    }

    /**
     * @return array
     */
    private function condenseReactions(): array
    {
        return $this->collection
            ->transform(fn (MessageReaction $reaction) => $this->makeResource($reaction))
            ->filter()
            ->uniqueStrict('reaction')
            ->pluck('reaction')
            ->mapWithKeys(fn (string $reaction) => [
                $reaction => $this->collection->where('reaction', $reaction)->values(),
            ])
            ->toArray();
    }

    /**
     * We go ahead and attempt to create and resolve each individual
     * resource, returning null should one fail.
     *
     * @param  MessageReaction  $reaction
     * @return array|null
     */
    private function makeResource(MessageReaction $reaction): ?array
    {
        try {
            return (new MessageReactionResource($reaction))->resolve();
        } catch (Throwable $t) {
            report($t);
        }

        return null;
    }
}
