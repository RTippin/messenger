<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageEdit;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessageEditTest extends FeatureTestCase
{
    private MessengerProvider $tippin;
    private Message $message;
    private MessageEdit $edited;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $group = $this->createGroupThread($this->tippin);
        $this->message = $this->createMessage($group, $this->tippin);
        $this->edited = $this->message->edits()->create([
            'body' => 'EDITED',
            'edited_at' => now(),
        ]);
    }

    /** @test */
    public function it_exists()
    {
        $this->assertDatabaseCount('message_edits', 1);
        $this->assertDatabaseHas('message_edits', [
            'id' => $this->edited->id,
        ]);
        $this->assertInstanceOf(MessageEdit::class, $this->edited);
    }

    /** @test */
    public function it_cast_attributes()
    {
        $this->assertInstanceOf(Carbon::class, $this->edited->edited_at);
    }

    /** @test */
    public function it_has_relation()
    {
        $this->assertSame($this->message->id, $this->edited->message->id);
        $this->assertInstanceOf(Message::class, $this->edited->message);
    }
}
