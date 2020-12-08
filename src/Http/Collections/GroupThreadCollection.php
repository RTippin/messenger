<?php

namespace RTippin\Messenger\Http\Collections;

use Exception;
use Illuminate\Http\Request;
use RTippin\Messenger\Http\Collections\Base\MessengerCollection;
use RTippin\Messenger\Http\Resources\ThreadResource;
use Throwable;

class GroupThreadCollection extends MessengerCollection
{
    /**
     * GroupThreadCollection constructor.
     *
     * @param $resource
     * @param bool $paginate
     * @param null $pageId
     */
    public function __construct($resource,
                                $paginate = false,
                                $pageId = null)
    {
        parent::__construct($resource);

        $this->paginate = $paginate;
        $this->pageId = $pageId;
        $this->collectionType = 'groups';
    }

    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     * @noinspection PhpMissingParamTypeInspection
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
    protected function makeResource($thread): ?array
    {
        try {
            return (new ThreadResource($thread))->resolve();
        } catch (Exception $e) {
            report($e);
        } catch (Throwable $t) {
            report($t);
        }

        return null;
    }
}
