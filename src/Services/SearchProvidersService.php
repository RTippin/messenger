<?php

namespace RTippin\Messenger\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Messenger as MessengerModel;
use RTippin\Messenger\Traits\Search;

class SearchProvidersService
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var null|string
     */
    private ?string $searchQuery = null;

    /**
     * @var null|array
     */
    private ?array $searchQueryItems = null;

    /**
     * @var Builder|null
     */
    private ?Builder $messengerQuery = null;

    /**
     * @var bool
     */
    private bool $onlySearchableForProvider = true;

    /**
     * SearchProvidersService constructor.
     *
     * @param  Messenger  $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * Enable searching all providers, not just allowed
     * interactions for current provider.
     *
     * @return $this
     */
    public function enableSearchAllProviders(): self
    {
        $this->onlySearchableForProvider = false;

        return $this;
    }

    /**
     * Set the query string, sanitize it, and break into array
     * Form the messenger whereHasMorph query.
     *
     * @param  string|null  $searchQuery
     * @return $this
     */
    public function search(string $searchQuery = null): self
    {
        return $this->sanitizeQuery($searchQuery)
            ->splitQuery()
            ->constructMessengerSearchQuery();
    }

    /**
     * Execute query using paginator.
     *
     * @return LengthAwarePaginator
     */
    public function paginate(): LengthAwarePaginator
    {
        if (! $this->passesQueryLength() || is_null($this->messengerQuery)) {
            return $this->sendEmptyPaginator();
        }

        return $this->messengerQuery->paginate(
            $this->messenger->getSearchPageCount()
        );
    }

    /**
     * Execute query and return query collection results.
     *
     * @return DBCollection|null
     */
    public function get(): ?DBCollection
    {
        return is_null($this->messengerQuery)
            ? null
            : $this->messengerQuery
                ->limit($this->messenger->getSearchPageCount())
                ->get();
    }

    /**
     * @return string|null
     */
    public function getSearchQuery(): ?string
    {
        return $this->searchQuery;
    }

    /**
     * @return array|null
     */
    public function getSearchQueryItems(): ?array
    {
        return $this->searchQueryItems;
    }

    /**
     * @return bool
     */
    private function passesQueryLength(): bool
    {
        return ! is_null($this->searchQuery) && strlen($this->searchQuery) >= 2;
    }

    /**
     * @return LengthAwarePaginator
     */
    private function sendEmptyPaginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [], 0, $this->messenger->getSearchPageCount()
        );
    }

    /**
     * @param $query
     * @return SearchProvidersService
     */
    private function sanitizeQuery($query): self
    {
        $this->searchQuery = str_replace([
            '%',
            '<',
            '>',
            '`',
            '"',
        ], '', $query);

        return $this;
    }

    /**
     * @return SearchProvidersService
     */
    private function splitQuery(): self
    {
        $this->searchQueryItems = Collection::make(preg_split('/[ \n,]+/', $this->searchQuery))
            ->filter(fn ($value) => strlen($value) >= 2)
            ->uniqueStrict()
            ->take(4)
            ->values()
            ->toArray();

        return $this;
    }

    /**
     * Set the reverse provider search builder based on allowed providers.
     *
     * @return SearchProvidersService
     */
    private function constructMessengerSearchQuery(): self
    {
        $searchable = $this->getAllowedSearchable();

        if (is_null($searchable) || ! count($searchable)) {
            $this->messengerQuery = null;
        } else {
            $this->messengerQuery = MessengerModel::whereHasMorph('owner', $searchable,
                function (Builder $query, $provider) {
                    /** @var Search $provider */
                    $provider::getProviderSearchableBuilder(
                        $query,
                        $this->searchQuery,
                        $this->searchQueryItems
                    );
                }
            )->with('owner');
        }

        return $this;
    }

    /**
     * @return array|null
     */
    private function getAllowedSearchable(): ?array
    {
        return $this->onlySearchableForProvider
            ? $this->messenger->getSearchableForCurrentProvider()
            : $this->messenger->getAllSearchableProviders();
    }
}
