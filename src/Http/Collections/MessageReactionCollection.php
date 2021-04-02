<?php

namespace RTippin\Messenger\Http\Collections;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use RTippin\Messenger\Http\Resources\MessageReactionResource;
use RTippin\Messenger\Models\MessageReaction;
use Throwable;

class MessageReactionCollection extends ResourceCollection
{
    /**
     * @var Collection
     */
    private Collection $transformed;

    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public function toArray($request): array
    {
        $this->safeTransformer();

        return [
            'data' => $this->condenseReactions(),
            'meta' => [
                'total' => $this->collection->count(),
                'total_unique' => $this->collection->uniqueStrict('reaction')->count(),
            ],
        ];
    }

    /**
     * Transform the collection to resources, safe guarding against
     * breaking the entire collection should one resource fail.
     *
     * @return void
     */
    private function safeTransformer(): void
    {
        $this->transformed = $this->collection
            ->map(fn ($reaction) => $this->makeResource($reaction))
            ->reject(fn ($reaction) => is_null($reaction));
    }

    /**
     * We go ahead and attempt to create and resolve each individual
     * resource, returning null should one fail.
     *
     * @param MessageReaction $reaction
     * @return array|null
     */
    private function makeResource(MessageReaction $reaction): ?array
    {
        try {
            return (new MessageReactionResource($reaction))->resolve();
        } catch (Exception $e) {
            report($e);
        } catch (Throwable $t) {
            report($t);
        }

        return null;
    }

    /**
     * @return array
     */
    private function condenseReactions(): array
    {
        return $this->transformed
            ->uniqueStrict('reaction')
            ->pluck('reaction')
            ->mapWithKeys(fn ($reaction) => [
                $reaction => $this->transformed->reject(fn ($react) => $react['reaction'] !== $reaction)->values(),
            ])
            ->toArray();
    }
}
