<?php

namespace RTippin\Messenger\Tests\Messenger;

use Illuminate\Pagination\LengthAwarePaginator;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\SearchProvidersService;
use RTippin\Messenger\Tests\FeatureTestCase;

class SearchProvidersServiceTest extends FeatureTestCase
{
    private SearchProvidersService $search;

    protected function setUp(): void
    {
        parent::setUp();

        $this->search = app(SearchProvidersService::class);
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $config = $app->get('config');

        $config->set('messenger.providers.user.provider_interactions.can_search', false);
    }

    /** @test */
    public function search_returns_empty_paginator()
    {
        $search = $this->search->paginate();

        $this->assertInstanceOf(LengthAwarePaginator::class, $search);

        $this->assertSame(0, $search->toArray()['total']);
    }

    /** @test */
    public function search_returns_paginator()
    {
        $search = $this->search->enableSearchAllProviders()->search('Doe Dev')->paginate();

        $this->assertInstanceOf(LengthAwarePaginator::class, $search);

        $this->assertSame(2, $search->toArray()['total']);
    }

    /** @test */
    public function search_returns_null_on_get_with_no_queries()
    {
        $this->assertNull($this->search->search('')->get());

        $this->assertNull($this->search->search(null)->get());
    }

    /** @test */
    public function search_returns_one_result()
    {
        $search = $this->search->enableSearchAllProviders()->search('Tippin')->get()->toArray();

        $this->assertCount(1, $search);

        $this->assertSame('Richard Tippin', $search[0]['owner']['name']);
    }

    /** @test */
    public function search_returns_multiple_result()
    {
        $search = $this->search->enableSearchAllProviders()->search('Doe Dev')->get()->toArray();

        $this->assertCount(2, $search);

        $this->assertSame('John Doe', $search[0]['owner']['name']);

        $this->assertSame('Developers', $search[1]['owner']['company_name']);
    }

    /** @test */
    public function search_ignores_providers_the_current_provider_is_not_allowed_to_search_for()
    {
        Messenger::setProvider($this->userTippin());

        $search = $this->search->search('Dev Laravel Doe')->get()->toArray();

        $this->assertCount(1, $search);

        $this->assertSame('John Doe', $search[0]['owner']['name']);
    }

    /** @test */
    public function search_returns_one_result_that_matches_exact_email()
    {
        $search = $this->search->enableSearchAllProviders()->search('doe@example.net')->get()->toArray();

        $this->assertCount(1, $search);

        $this->assertSame('John Doe', $search[0]['owner']['name']);
    }

    /** @test */
    public function search_removes_special_characters()
    {
        $search = $this->search->search('%T<E>S`T"ING')->getSearchQuery();

        $this->assertSame('TESTING', $search);
    }

    /** @test */
    public function search_takes_first_four_queries()
    {
        $search = $this->search->search('Tippin John Doe Jane Test Foo')->getSearchQueryItems();

        $this->assertSame(['Tippin', 'John', 'Doe', 'Jane'], $search);
    }

    /** @test */
    public function search_ignores_strings_of_length_less_than_two()
    {
        $search = $this->search->search('Ti John D Y Z')->getSearchQueryItems();

        $this->assertSame(['Ti', 'John'], $search);
    }

    /**
     * @test
     * @dataProvider splitQueries
     * @param $query
     * @param $expected
     */
    public function search_splits_query_by_space_or_comma_into_array($query, $expected)
    {
        $search = $this->search->search($query)->getSearchQueryItems();

        $this->assertSame($expected, $search);
    }

    /**
     * @test
     * @dataProvider splitQueriesDuplicates
     * @param $query
     * @param $expected
     */
    public function search_splits_query_removes_duplicates($query, $expected)
    {
        $search = $this->search->search($query)->getSearchQueryItems();

        $this->assertSame($expected, $search);
    }

    public function splitQueries(): array
    {
        return [
            ['Tippin', ['Tippin']],
            ['Tippin John', ['Tippin', 'John']],
            ['Tippin,John,Doe', ['Tippin', 'John', 'Doe']],
            ['Tippin,John, Doe Jane', ['Tippin', 'John', 'Doe', 'Jane']],
        ];
    }

    public function splitQueriesDuplicates(): array
    {
        return [
            ['Tippin Tippin', ['Tippin']],
            ['Tippin John Tippin Doe', ['Tippin', 'John', 'Doe']],
            ['Doe Doe Doe Tippin Doe John', ['Doe', 'Tippin', 'John']],
        ];
    }
}
