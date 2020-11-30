<?php

namespace RTippin\Messenger\Http\Collections;

use RTippin\Messenger\Http\Collections\Base\MessengerCollection;
use Exception;
use Illuminate\Http\Request;
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
     * OwnerCollection constructor.
     *
     * @param $resource
     * @param null $searchQuery
     * @param null $searchQueryItems
     * @param bool $addOptions
     */
    public function __construct($resource,
                                $searchQuery = null,
                                $searchQueryItems = null,
                                $addOptions = false)
    {
        parent::__construct($resource);

        $this->addOptions = $addOptions;
        $this->searchQuery = $searchQuery;
        $this->searchQueryItems = $searchQueryItems;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public function toArray($request)
    {
        return $this->safeTransformer();
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param Request $request
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public function with($request)
    {
        return [
            'meta' => [
                'search' => $this->searchQuery,
                'search_items' => $this->searchQueryItems
            ]
        ];
    }
    /**
     * @inheritDoc
     */
    protected function makeResource($messenger): ?array
    {
        try{
            return (new ProviderResource($messenger->owner, $this->addOptions))->resolve();
        }catch (Exception $e){
            report($e);
        }catch(Throwable $t){
            report($t);
        }

        return null;
    }

}
