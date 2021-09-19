<?php

namespace RTippin\Messenger\Http\Collections;

use Illuminate\Http\Request;
use RTippin\Messenger\Http\Collections\Base\MessengerCollection;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Models\Messenger;
use Throwable;

class SearchCollection extends MessengerCollection
{
    /**
     * @var bool
     */
    protected bool $addOptions;

    /**
     * @var null|string
     */
    private ?string $searchQuery;

    /**
     * @var null|array
     */
    private ?array $searchQueryItems;

    /**
     * SearchCollection constructor.
     *
     * @param $resource
     * @param  null  $searchQuery
     * @param  array|null  $searchQueryItems
     * @param  bool  $addOptions
     */
    public function __construct($resource,
                                $searchQuery = null,
                                ?array $searchQueryItems = null,
                                bool $addOptions = false)
    {
        parent::__construct($resource);

        $this->addOptions = $addOptions;
        $this->searchQuery = $searchQuery;
        $this->searchQueryItems = $searchQueryItems;
    }

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
     * Get additional data that should be returned with the resource array.
     *
     * @param  Request  $request
     * @return array
     */
    public function with($request): array
    {
        return [
            'meta' => [
                'search' => $this->searchQuery,
                'search_items' => $this->searchQueryItems,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function makeResource($resource): ?array
    {
        try {
            /** @var Messenger $resource */
            return (new ProviderResource($resource->owner, $this->addOptions))->resolve();
        } catch (Throwable $t) {
            report($t);
        }

        return null;
    }
}
