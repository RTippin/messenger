<?php

namespace RTippin\Messenger\Http\Collections;

use Illuminate\Http\Request;
use RTippin\Messenger\Http\Collections\Base\MessengerCollection;
use RTippin\Messenger\Http\Resources\CallResource;
use RTippin\Messenger\Models\Thread;
use Throwable;

class CallCollection extends MessengerCollection
{
    /**
     * CallCollection constructor.
     *
     * @param $resource
     * @param  Thread  $thread
     * @param  bool  $paginate
     * @param  string|null  $pageId
     */
    public function __construct($resource,
                                Thread $thread,
                                bool $paginate = false,
                                ?string $pageId = null)
    {
        parent::__construct($resource);

        $this->paginate = $paginate;
        $this->thread = $thread;
        $this->collectionType = 'calls';
        $this->pageId = $pageId;
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
        ];
    }

    /**
     * @inheritDoc
     */
    protected function makeResource($resource): ?array
    {
        try {
            return (new CallResource($resource, $this->thread))->resolve();
        } catch (Throwable $t) {
            report($t);
        }

        return null;
    }
}
