<?php

namespace RTippin\Messenger\Http\Collections;

use Illuminate\Http\Request;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Collections\Base\MessengerCollection;
use RTippin\Messenger\Http\Resources\ThreadResource;
use Throwable;

class PrivateThreadCollection extends MessengerCollection
{
    /**
     * GroupThreadCollection constructor.
     *
     * @param $resource
     * @param  bool  $paginate
     * @param  string|null  $pageId
     */
    public function __construct($resource,
                                bool $paginate = false,
                                ?string $pageId = null)
    {
        parent::__construct($resource);

        $this->paginate = $paginate;
        $this->pageId = $pageId;
        $this->collectionType = 'privates';
    }

    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->safeTransformer(),
            'meta' => [
                'index' => $this->isIndex(),
                'page_id' => $this->pageId,
                'next_page_id' => $this->nextPageId(),
                'next_page_route' => $this->nextPageLink(),
                'final_page' => $this->isFinalPage(),
                'per_page' => $this->perPageConfig(),
                'results' => $this->collection->count(),
                'total' => $this->grandTotal(),
            ],
            'system_features' => Messenger::getSystemFeatures(),
        ];
    }

    /**
     * @inheritDoc
     */
    protected function makeResource($resource): ?array
    {
        try {
            return (new ThreadResource($resource))->resolve();
        } catch (Throwable $t) {
            report($t);
        }

        return null;
    }
}
