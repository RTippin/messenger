<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class RemoveMessageEmbedsTest extends HttpTestCase
{
    /** @test */
    public function non_participant_is_forbidden()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.messages.embeds.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function message_owner_can_remove_embeds()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $message = Message::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.messages.embeds.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function non_owner_forbidden_to_remove_embeds()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.messages.embeds.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_remove_message_embeds_when_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $message = Message::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.messages.embeds.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_remove_embeds_from_non_owned_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.messages.embeds.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertStatus(204);
    }
}
