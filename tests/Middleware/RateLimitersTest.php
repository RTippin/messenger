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
    public function setting_api_limit_to_zero_removes_rate_limiter()
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
    public function setting_search_api_limit_to_zero_removes_rate_limiter()
    {
        Messenger::setApiRateLimit(0)->setSearchRateLimit(0);
        $this->actingAs($this->tippin);

        $response = $this->getJson(route('api.messenger.search'));

        $this->assertFalse($response->headers->has('X-Ratelimit-Limit'));
        $this->assertFalse($response->headers->has('X-RateLimit-Remaining'));
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
    public function setting_message_api_limit_to_zero_removes_rate_limiter()
    {
        Messenger::setApiRateLimit(0)->setMessageRateLimit(0);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $response = $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ]);

        $this->assertFalse($response->headers->has('X-Ratelimit-Limit'));
        $this->assertFalse($response->headers->has('X-RateLimit-Remaining'));
    }

    /**
     * @test
     *
     * @dataProvider attachments
     *
     * @param  $route
     * @param  $key
     * @param  $attachment
     */
    public function store_attachment_message_api_limits_request_15_per_minute($route, $key, $attachment)
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $response = $this->postJson(route($route, [
            'thread' => $thread->id,
        ]), [
            $key => $attachment,
            'temporary_id' => '123-456-789',
        ]);

        $this->assertEquals(15, $response->headers->get('X-Ratelimit-Limit'));
        $this->assertEquals(14, $response->headers->get('X-RateLimit-Remaining'));
    }

    /**
     * @test
     *
     * @dataProvider attachments
     *
     * @param  $route
     * @param  $key
     * @param  $attachment
     */
    public function setting_attachment_message_api_limit_to_zero_removes_rate_limiter($route, $key, $attachment)
    {
        Messenger::setApiRateLimit(0)->setAttachmentRateLimit(0);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $response = $this->postJson(route($route, [
            'thread' => $thread->id,
        ]), [
            $key => $attachment,
            'temporary_id' => '123-456-789',
        ]);

        $this->assertFalse($response->headers->has('X-Ratelimit-Limit'));
        $this->assertFalse($response->headers->has('X-RateLimit-Remaining'));
    }

    public static function attachments(): array
    {
        return [
            'Image' => ['api.messenger.threads.images.store', 'image', UploadedFile::fake()->image('picture.png')],
            'Document' => ['api.messenger.threads.documents.store', 'document', UploadedFile::fake()->create('test.pdf', 500, 'application/pdf')],
            'Audio' => ['api.messenger.threads.audio.store', 'audio', UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg')],
            'Video' => ['api.messenger.threads.videos.store', 'video', UploadedFile::fake()->create('video.mp4', 500, 'video/mp4')],
        ];
    }
}
