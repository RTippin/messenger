<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class SearchTest extends FeatureTestCase
{
    /** @test */
    public function test_guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.search', [
            'query' => 'john',
        ]))
            ->assertUnauthorized();
    }

    /** @test */
    public function test_empty_search_returns_no_results()
    {
        $this->actingAs(UserModel::first());

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
    public function test_search_finds_user()
    {
        $user = UserModel::first();

        $this->actingAs(UserModel::first());

        $this->getJson(route('api.messenger.search', [
            'query' => 'tippin',
        ]))
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'name' => $user->name(),
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
    public function test_search_for_user_without_messenger_returns_no_results()
    {
        UserModel::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);

        $this->actingAs(UserModel::first());

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
    public function test_multiple_search_queries_separated_by_space_returns_multiple_results()
    {
        $this->actingAs(UserModel::first());

        $this->getJson(route('api.messenger.search', [
            'query' => 'tippin john',
        ]))
            ->assertJsonCount(2, 'data')
            ->assertJson([
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
    public function test_search_strips_special_characters()
    {
        $this->actingAs(UserModel::first());

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
    public function test_exact_email_returns_user_result()
    {
        $user = UserModel::first();

        $this->actingAs($user);

        $this->getJson(route('api.messenger.search', [
            'query' => 'richard.tippin@gmail.com',
        ]))
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'name' => $user->name(),
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
    public function test_incomplete_email_returns_no_results()
    {
        $this->actingAs(UserModel::first());

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
