<?php

namespace RTippin\Messenger\Tests\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Services\SearchProvidersService;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;

class SearchProvidersServiceTest extends FeatureTestCase
{
    private SearchProvidersService $search;

    protected function setUp(): void
    {
        parent::setUp();

        $this->search = app(SearchProvidersService::class);
    }

    /** @test */
    public function it_returns_empty_paginator()
    {
        $search = $this->search->paginate();

        $this->assertInstanceOf(LengthAwarePaginator::class, $search);
        $this->assertSame(0, $search->toArray()['total']);
    }

    /** @test */
    public function it_returns_paginator()
    {
        $search = $this->search->enableSearchAllProviders()->search('Doe Dev')->paginate();

        $this->assertInstanceOf(LengthAwarePaginator::class, $search);
        $this->assertSame(2, $search->toArray()['total']);
    }

    /** @test */
    public function it_returns_null_on_get_with_no_queries()
    {
        $this->assertNull($this->search->search('')->get());
        $this->assertNull($this->search->search(null)->get());
    }

    /** @test */
    public function it_returns_one_result()
    {
        $search = $this->search->enableSearchAllProviders()->search('Tippin')->get()->toArray();

        $this->assertCount(1, $search);
        $this->assertSame('Richard Tippin', $search[0]['owner']['name']);
    }

    /** @test */
    public function it_returns_multiple_result()
    {
        $search = $this->search->enableSearchAllProviders()->search('Doe Dev')->get()->toArray();

        $this->assertCount(2, $search);
        $this->assertSame('John Doe', $search[0]['owner']['name']);
        $this->assertSame('Developers', $search[1]['owner']['company_name']);
    }

    /** @test */
    public function it_ignores_providers_the_current_provider_is_not_allowed_to_search()
    {
        UserModel::$cantSearch = [CompanyModel::class];
        Messenger::registerProviders([UserModel::class, CompanyModel::class]);
        Messenger::setProvider($this->tippin);

        $search = $this->search->search('Developers Doe')->get()->toArray();

        $this->assertCount(1, $search);
        $this->assertSame('John Doe', $search[0]['owner']['name']);
    }

    /** @test */
    public function it_returns_one_result_that_matches_exact_email()
    {
        $search = $this->search->enableSearchAllProviders()->search('doe@example.net')->get()->toArray();

        $this->assertCount(1, $search);
        $this->assertSame('John Doe', $search[0]['owner']['name']);
    }

    /** @test */
    public function it_removes_special_characters()
    {
        $search = $this->search->search('%T<E>S`T"ING')->getSearchQuery();

        $this->assertSame('TESTING', $search);
    }

    /** @test */
    public function it_takes_first_four_queries()
    {
        $search = $this->search->search('Tippin John Doe Jane Test Foo')->getSearchQueryItems();

        $this->assertSame(['Tippin', 'John', 'Doe', 'Jane'], $search);
    }

    /** @test */
    public function it_ignores_strings_of_length_less_than_two()
    {
        $search = $this->search->search('Ti John D Y Z')->getSearchQueryItems();

        $this->assertSame(['Ti', 'John'], $search);
    }

    /**
     * @test
     *
     * @dataProvider splitQueries
     *
     * @param  $query
     * @param  $expected
     */
    public function it_splits_query_by_space_or_comma_into_array($query, $expected)
    {
        $search = $this->search->search($query)->getSearchQueryItems();

        $this->assertSame($expected, $search);
    }

    /**
     * @test
     *
     * @dataProvider splitQueriesDuplicates
     *
     * @param  $query
     * @param  $expected
     */
    public function it_splits_query_removes_duplicates($query, $expected)
    {
        $search = $this->search->search($query)->getSearchQueryItems();

        $this->assertSame($expected, $search);
    }

    public static function splitQueries(): array
    {
        return [
            ['Tippin', ['Tippin']],
            ['Tippin John', ['Tippin', 'John']],
            ['Tippin,John,Doe', ['Tippin', 'John', 'Doe']],
            ['Tippin,John, Doe Jane', ['Tippin', 'John', 'Doe', 'Jane']],
        ];
    }

    public static function splitQueriesDuplicates(): array
    {
        return [
            ['Tippin Tippin', ['Tippin']],
            ['Tippin John Tippin Doe', ['Tippin', 'John', 'Doe']],
            ['Doe Doe Doe Tippin Doe John', ['Doe', 'Tippin', 'John']],
        ];
    }
}
