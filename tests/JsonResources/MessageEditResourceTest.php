<?php

namespace RTippin\Messenger\Tests\JsonResources;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Http\Resources\MessageEditResource;
use RTippin\Messenger\Models\MessageEdit;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessageEditResourceTest extends FeatureTestCase
{
    /** @test */
    public function it_transforms_message_edit()
    {
        $created = now()->subMinutes(5)->format('Y-m-d H:i:s.u');
        Carbon::setTestNow($created);
        $message = $this->createMessage($this->createGroupThread($this->tippin), $this->tippin);
        $edit = MessageEdit::create([
            'message_id' => $message->id,
            'body' => 'Edit',
            'edited_at' => $created,
        ]);

        $resource = (new MessageEditResource($edit))->resolve();

        $this->assertSame('Edit', $resource['body']);
        $this->assertSame($created, $resource['edited_at']->format('Y-m-d H:i:s.u'));
        $this->assertCount(2, $resource);
    }
}
