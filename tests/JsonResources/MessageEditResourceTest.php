<?php

namespace RTippin\Messenger\Tests\JsonResources;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Http\Resources\MessageEditResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageEdit;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\MessageTransformer;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessageEditResourceTest extends FeatureTestCase
{
    /** @test */
    public function it_transforms_message_edit()
    {
        $created = now()->subMinutes(5)->format('Y-m-d H:i:s.u');
        Carbon::setTestNow($created);
        $message = Message::factory()->for(Thread::factory()->create())->owner($this->tippin)->create();
        $edit = MessageEdit::factory()->for($message)->create();

        $resource = (new MessageEditResource($edit))->resolve();

        $this->assertSame(MessageTransformer::sanitizedBody($edit->body), $resource['body']);
        $this->assertSame($created, $resource['edited_at']->format('Y-m-d H:i:s.u'));
        $this->assertCount(2, $resource);
    }
}
