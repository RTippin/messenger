<?php

namespace RTippin\Messenger\Tests\Middleware;

use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class RateLimitersTest extends FeatureTestCase
{
    /** @test */
    public function general_api_limits_request_1000_per_minute()
    {
        $this->actingAs($this->tippin);

        $response = $this->getJson(route('api.messenger.threads.index'));

        $this->assertEquals(1000, $response->headers->get('X-Ratelimit-Limit'));
        $this->assertEquals(999, $response->headers->get('X-RateLimit-Remaining'));
    }

    /** @test */
    public function setting_limit_to_zero_results_in_unlimited_request_per_minute()
    {
        Messenger::setApiRateLimit(0);
        $this->actingAs($this->tippin);

        $response = $this->getJson(route('api.messenger.threads.index'));

        $this->assertFalse($response->headers->has('X-Ratelimit-Limit'));
        $this->assertFalse($response->headers->has('X-RateLimit-Remaining'));
    }

    /** @test */
    public function search_api_limits_request_45_per_minute()
    {
        $this->actingAs($this->tippin);

        $response = $this->getJson(route('api.messenger.search'));

        $this->assertEquals(45, $response->headers->get('X-Ratelimit-Limit'));
        $this->assertEquals(44, $response->headers->get('X-RateLimit-Remaining'));
    }

    /** @test */
    public function store_message_api_limits_request_60_per_minute()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $response = $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ]);

        $this->assertEquals(60, $response->headers->get('X-Ratelimit-Limit'));
        $this->assertEquals(59, $response->headers->get('X-RateLimit-Remaining'));
    }

    /** @test */
    public function store_image_message_api_limits_request_15_per_minute()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $response = $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $thread->id,
        ]), [
            'image' => UploadedFile::fake()->image('picture.jpg'),
            'temporary_id' => '123-456-789',
        ]);

        $this->assertEquals(15, $response->headers->get('X-Ratelimit-Limit'));
        $this->assertEquals(14, $response->headers->get('X-RateLimit-Remaining'));
    }

    /** @test */
    public function store_document_message_api_limits_request_15_per_minute()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $response = $this->postJson(route('api.messenger.threads.documents.store', [
            'thread' => $thread->id,
        ]), [
            'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            'temporary_id' => '123-456-789',
        ]);

        $this->assertEquals(15, $response->headers->get('X-Ratelimit-Limit'));
        $this->assertEquals(14, $response->headers->get('X-RateLimit-Remaining'));
    }
}
