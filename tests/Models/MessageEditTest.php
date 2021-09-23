<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageEdit;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessageEditTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $edit = MessageEdit::factory()->for(
            Message::factory()->for(
                Thread::factory()->create()
            )->owner($this->tippin)->create()
        )->create();

        $this->assertDatabaseCount('message_edits', 1);
        $this->assertDatabaseHas('message_edits', [
            'id' => $edit->id,
        ]);
        $this->assertInstanceOf(MessageEdit::class, $edit);
    }

    /** @test */
    public function it_cast_attributes()
    {
        $edit = MessageEdit::factory()->for(
            Message::factory()->for(
                Thread::factory()->create()
            )->owner($this->tippin)->create()
        )->create();

        $this->assertInstanceOf(Carbon::class, $edit->edited_at);
    }

    /** @test */
    public function it_has_relation()
    {
        $message = Message::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->create();
        $edit = MessageEdit::factory()->for($message)->create();

        $this->assertSame($message->id, $edit->message->id);
        $this->assertInstanceOf(Message::class, $edit->message);
    }
}
