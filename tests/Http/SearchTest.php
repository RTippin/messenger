<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Tests\HttpTestCase;

class SearchTest extends HttpTestCase
{
    /** @test */
    public function empty_search_returns_no_results()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.search'))
            ->assertJsonCount(0, 'data')
            ->assertJson([
                'meta' => [
                    'total' => 0,
                    'search_items' => [],
                    'search' => '',
                ],
            ]);
    }

    /** @test */
    public function search_finds_user()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.search', [
            'query' => 'tippin',
        ]))
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'name' => 'Richard Tippin',
                        'provider_alias' => 'user',
                        'provider_id' => $this->tippin->getKey(),
                    ],
                ],
                'meta' => [
                    'total' => 1,
                    'search_items' => [
                        'tippin',
                    ],
                    'search' => 'tippin',
                ],
            ]);
    }

    /** @test */
    public function search_finds_company()
    {
        $this->actingAs($this->developers);

        $this->getJson(route('api.messenger.search', [
            'query' => 'developers',
        ]))
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'name' => 'Developers',
                        'provider_alias' => 'company',
                        'provider_id' => $this->developers->getKey(),
                    ],
                ],
                'meta' => [
                    'total' => 1,
                    'search_items' => [
                        'developers',
                    ],
                    'search' => 'developers',
                ],
            ]);
    }

    /** @test */
    public function search_for_user_without_messenger_returns_no_results()
    {
        $this->createJaneSmith();
        $this->actingAs($this->tippin);

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
                    'search' => 'jane',
                ],
            ]);
    }

    /** @test */
    public function multiple_search_queries_separated_by_space_returns_multiple_user_results()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.search', [
            'query' => 'tippin john',
        ]))
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'data' => [
                    [
                        'name' => 'Richard Tippin',
                        'provider_alias' => 'user',
                        'provider_id' => $this->tippin->getKey(),
                    ],
                    [
                        'name' => 'John Doe',
                        'provider_alias' => 'user',
                        'provider_id' => $this->doe->getKey(),
                    ],
                ],
                'meta' => [
                    'total' => 2,
                    'search_items' => [
                        'tippin',
                        'john',
                    ],
                    'search' => 'tippin john',
                ],
            ]);
    }

    /** @test */
    public function multiple_providers_search_queries_separated_by_space_returns_multiple_results()
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.search', [
            'query' => 'tippin developers',
        ]))
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'data' => [
                    [
                        'name' => 'Richard Tippin',
                        'provider_alias' => 'user',
                        'provider_id' => $this->tippin->getKey(),
                    ],
                    [
                        'name' => 'Developers',
                        'provider_alias' => 'company',
                        'provider_id' => $this->developers->getKey(),
                    ],
                ],
                'meta' => [
                    'total' => 2,
                    'search_items' => [
                        'tippin',
                        'developers',
                    ],
                    'search' => 'tippin developers',
                ],
            ]);
    }

    /** @test */
    public function search_strips_special_characters()
    {
        $this->actingAs($this->tippin);

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
                    'search' => 'tippin',
                ],
            ]);
    }

    /** @test */
    public function exact_email_returns_user_result()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.search', [
            'query' => 'tippindev@gmail.com',
        ]))
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'name' => 'Richard Tippin',
                        'provider_alias' => 'user',
                        'provider_id' => $this->tippin->getKey(),
                    ],
                ],
                'meta' => [
                    'total' => 1,
                    'search_items' => [
                        'tippindev@gmail.com',
                    ],
                    'search' => 'tippindev@gmail.com',
                ],
            ]);
    }

    /** @test */
    public function exact_email_returns_company_result()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.search', [
            'query' => 'developers@example.net',
        ]))
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'name' => 'Developers',
                        'provider_alias' => 'company',
                        'provider_id' => $this->developers->getKey(),
                    ],
                ],
                'meta' => [
                    'total' => 1,
                    'search_items' => [
                        'developers@example.net',
                    ],
                    'search' => 'developers@example.net',
                ],
            ]);
    }

    /** @test */
    public function incomplete_email_returns_no_results()
    {
        $this->actingAs($this->tippin);

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
                    'search' => 'richard.tippin',
                ],
            ]);
    }
}
