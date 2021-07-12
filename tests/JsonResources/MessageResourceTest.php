<?php

namespace RTippin\Messenger\Tests\JsonResources;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessageResourceTest extends FeatureTestCase
{
    /** @test */
    public function it_transforms_base_text_message()
    {
        Carbon::setTestNow($created = now()->format('Y-m-d H:i:s.u'));
        $private = Thread::factory()->create();
        $message = Message::factory()->for($private)->owner($this->tippin)->create(['body' => 'Test']);

        $resource = (new MessageResource($message, $private))->resolve();

        $this->assertSame($message->id, $resource['id']);
        $this->assertSame($private->id, $resource['thread_id']);
        $this->assertSame($created, $resource['created_at']->format('Y-m-d H:i:s.u'));
        $this->assertSame($created, $resource['updated_at']->format('Y-m-d H:i:s.u'));
        $this->assertEquals($this->tippin->getKey(), $resource['owner_id']);
        $this->assertSame($this->tippin->getMorphClass(), $resource['owner_type']);
        $this->assertIsArray($resource['owner']);
        $this->assertSame(0, $resource['type']);
        $this->assertSame('MESSAGE', $resource['type_verbose']);
        $this->assertFalse($resource['system_message']);
        $this->assertSame('Test', $resource['body']);
        $this->assertFalse($resource['edited']);
        $this->assertTrue($resource['embeds']);
        $this->assertFalse($resource['reacted']);
        $this->assertNull($resource['extra']);
        $this->assertIsArray($resource['meta']);
        $this->assertSame($private->id, $resource['meta']['thread_id']);
        $this->assertSame(1, $resource['meta']['thread_type']);
        $this->assertSame('PRIVATE', $resource['meta']['thread_type_verbose']);
        $this->assertArrayNotHasKey('thread_name', $resource['meta']);
        $this->assertArrayNotHasKey('thread_avatar', $resource['meta']);
        $this->assertArrayNotHasKey('temporary_id', $resource);
        $this->assertArrayNotHasKey('edited_history_route', $resource);
        $this->assertArrayNotHasKey('reactions', $resource);
        $this->assertArrayNotHasKey('reply_to_id', $resource);
        $this->assertArrayNotHasKey('reply_to', $resource);
        $this->assertArrayNotHasKey('image', $resource);
        $this->assertArrayNotHasKey('audio', $resource);
        $this->assertArrayNotHasKey('document', $resource);
    }

    /** @test */
    public function it_adds_thread_meta_if_group()
    {
        $group = Thread::factory()->group()->create(['subject' => 'Group']);
        $message = Message::factory()->for($group)->owner($this->tippin)->create();

        $resource = (new MessageResource($message, $group))->resolve();

        $this->assertIsArray($resource['meta']);
        $this->assertSame($group->id, $resource['meta']['thread_id']);
        $this->assertSame(2, $resource['meta']['thread_type']);
        $this->assertSame('GROUP', $resource['meta']['thread_type_verbose']);
        $this->assertArrayHasKey('thread_name', $resource['meta']);
        $this->assertArrayHasKey('thread_avatar', $resource['meta']);
        $this->assertSame('Group', $resource['meta']['thread_name']);
        $this->assertIsArray($resource['meta']['thread_avatar']);
    }

    /** @test */
    public function it_adds_temporary_id()
    {
        $private = Thread::factory()->create();
        $message = Message::factory()->for($private)->owner($this->tippin)->create();
        $message->setTemporaryId('1234-5678');

        $resource = (new MessageResource($message, $private))->resolve();

        $this->assertArrayHasKey('temporary_id', $resource);
        $this->assertSame('1234-5678', $resource['temporary_id']);
    }

    /** @test */
    public function it_adds_extra()
    {
        $private = Thread::factory()->create();
        $message = Message::factory()->for($private)->owner($this->tippin)->create(['extra' => ['test' => true]]);

        $resource = (new MessageResource($message, $private))->resolve();

        $this->assertNotNull($resource['extra']);
        $this->assertSame(['test' => true], $resource['extra']);
    }

    /** @test */
    public function it_adds_reactions()
    {
        $private = Thread::factory()->create();
        $message = Message::factory()->for($private)->owner($this->tippin)->reacted()->create();

        $resource = (new MessageResource($message, $private, true))->resolve();

        $this->assertTrue($resource['reacted']);
        $this->assertArrayHasKey('reactions', $resource);
        $this->assertIsArray($resource['reactions']);
    }

    /** @test */
    public function it_adds_reply_to_message()
    {
        $private = Thread::factory()->create();
        $reply = Message::factory()->for($private)->owner($this->tippin)->create();
        $message = Message::factory()->for($private)->owner($this->tippin)->reply($reply->id)->create();

        $resource = (new MessageResource($message, $private, true))->resolve();

        $this->assertArrayHasKey('reply_to_id', $resource);
        $this->assertSame($reply->id, $resource['reply_to_id']);
        $this->assertArrayHasKey('reply_to', $resource);
        $this->assertIsArray($resource['reply_to']);
    }

    /** @test */
    public function it_transforms_image_message()
    {
        $private = Thread::factory()->create();
        $message = Message::factory()->for($private)->owner($this->tippin)->image()->create();

        $resource = (new MessageResource($message, $private))->resolve();

        $this->assertSame(1, $resource['type']);
        $this->assertSame('IMAGE_MESSAGE', $resource['type_verbose']);
        $this->assertArrayHasKey('image', $resource);
        $this->assertIsArray($resource['image']);
    }

    /** @test */
    public function it_transforms_document_message()
    {
        $private = Thread::factory()->create();
        $message = Message::factory()->for($private)->owner($this->tippin)->document()->create();

        $resource = (new MessageResource($message, $private))->resolve();

        $this->assertSame(2, $resource['type']);
        $this->assertSame('DOCUMENT_MESSAGE', $resource['type_verbose']);
        $this->assertArrayHasKey('document', $resource);
    }

    /** @test */
    public function it_transforms_audio_message()
    {
        $private = Thread::factory()->create();
        $message = Message::factory()->for($private)->owner($this->tippin)->audio()->create();

        $resource = (new MessageResource($message, $private))->resolve();

        $this->assertSame(3, $resource['type']);
        $this->assertSame('AUDIO_MESSAGE', $resource['type_verbose']);
        $this->assertArrayHasKey('audio', $resource);
    }
}
