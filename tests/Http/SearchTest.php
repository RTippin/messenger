<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class SearchTest extends FeatureTestCase
{
    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.search', [
            'query' => 'john',
        ]))
            ->assertUnauthorized();
    }

    /** @test */
    public function empty_search_returns_no_results()
    {
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.search'))
            ->assertJsonCount(0, 'data')
            ->assertJson([
                'meta' => [
                    'total' => 0,
                    'search_items' => [],
                    'per_page' => Messenger::getSearchPageCount(),
                    'search' => '',
                ],
            ]);
    }

    /** @test */
    public function search_finds_user()
    {
        $tippin = $this->userTippin();

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.search', [
            'query' => 'tippin',
        ]))
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'name' => 'Richard Tippin',
                        'provider_alias' => 'user',
                        'provider_id' => $tippin->getKey(),
                    ],
                ],
                'meta' => [
                    'total' => 1,
                    'search_items' => [
                        'tippin',
                    ],
                    'per_page' => Messenger::getSearchPageCount(),
                    'search' => 'tippin',
                ],
            ]);
    }

    /** @test */
    public function search_finds_company()
    {
        $developers = $this->companyDevelopers();

        $this->actingAs($developers);

        $this->getJson(route('api.messenger.search', [
            'query' => 'developers',
        ]))
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'name' => 'Developers',
                        'provider_alias' => 'company',
                        'provider_id' => $developers->getKey(),
                    ],
                ],
                'meta' => [
                    'total' => 1,
                    'search_items' => [
                        'developers',
                    ],
                    'per_page' => Messenger::getSearchPageCount(),
                    'search' => 'developers',
                ],
            ]);
    }

    /** @test */
    public function search_for_user_without_messenger_returns_no_results()
    {
        $this->createJaneSmith();

        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.search', [
            'query' => 'jane',
        ]))
            ->assertJsonCount(0, 'data')
            ->assertJson([
                'meta' => [
                    'total' => 0,
                    'search_items' => [
                        'jane',
                    ],
                    'per_page' => Messenger::getSearchPageCount(),
                    'search' => 'jane',
                ],
            ]);
    }

    /** @test */
    public function multiple_search_queries_separated_by_space_returns_multiple_user_results()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.search', [
            'query' => 'tippin john',
        ]))
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'data' => [
                    [
                        'name' => 'Richard Tippin',
                        'provider_alias' => 'user',
                        'provider_id' => $tippin->getKey(),
                    ],
                    [
                        'name' => 'John Doe',
                        'provider_alias' => 'user',
                        'provider_id' => $doe->getKey(),
                    ],
                ],
                'meta' => [
                    'total' => 2,
                    'search_items' => [
                        'tippin',
                        'john',
                    ],
                    'per_page' => Messenger::getSearchPageCount(),
                    'search' => 'tippin john',
                ],
            ]);
    }

    /** @test */
    public function multiple_providers_search_queries_separated_by_space_returns_multiple_results()
    {
        $tippin = $this->userTippin();

        $laravel = $this->companyLaravel();

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.search', [
            'query' => 'tippin laravel',
        ]))
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'data' => [
                    [
                        'name' => 'Richard Tippin',
                        'provider_alias' => 'user',
                        'provider_id' => $tippin->getKey(),
                    ],
                    [
                        'name' => 'Laravel',
                        'provider_alias' => 'company',
                        'provider_id' => $laravel->getKey(),
                    ],
                ],
                'meta' => [
                    'total' => 2,
                    'search_items' => [
                        'tippin',
                        'laravel',
                    ],
                    'per_page' => Messenger::getSearchPageCount(),
                    'search' => 'tippin laravel',
                ],
            ]);
    }

    /** @test */
    public function search_strips_special_characters()
    {
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.search', [
            'query' => '%`tippin"><',
        ]))
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'meta' => [
                    'total' => 1,
                    'search_items' => [
                        'tippin',
                    ],
                    'per_page' => Messenger::getSearchPageCount(),
                    'search' => 'tippin',
                ],
            ]);
    }

    /** @test */
    public function exact_email_returns_user_result()
    {
        $tippin = $this->userTippin();

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.search', [
            'query' => 'richard.tippin@gmail.com',
        ]))
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'name' => 'Richard Tippin',
                        'provider_alias' => 'user',
                        'provider_id' => $tippin->getKey(),
                    ],
                ],
                'meta' => [
                    'total' => 1,
                    'search_items' => [
                        'richard.tippin@gmail.com',
                    ],
                    'per_page' => Messenger::getSearchPageCount(),
                    'search' => 'richard.tippin@gmail.com',
                ],
            ]);
    }

    /** @test */
    public function exact_email_returns_company_result()
    {
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.search', [
            'query' => 'developers@example.net',
        ]))
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'name' => 'Developers',
                        'provider_alias' => 'company',
                        'provider_id' => $this->companyDevelopers()->getKey(),
                    ],
                ],
                'meta' => [
                    'total' => 1,
                    'search_items' => [
                        'developers@example.net',
                    ],
                    'per_page' => Messenger::getSearchPageCount(),
                    'search' => 'developers@example.net',
                ],
            ]);
    }

    /** @test */
    public function incomplete_email_returns_no_results()
    {
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.search', [
            'query' => 'richard.tippin',
        ]))
            ->assertJsonCount(0, 'data')
            ->assertJson([
                'meta' => [
                    'total' => 0,
                    'search_items' => [
                        'richard.tippin',
                    ],
                    'per_page' => Messenger::getSearchPageCount(),
                    'search' => 'richard.tippin',
                ],
            ]);
    }
}
