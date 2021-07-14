<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class ThreadsTest extends HttpTestCase
{
    /** @test */
    public function user_has_no_threads()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.index'))
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function user_belongs_to_two_threads()
    {
        $this->logCurrentRequest();
        $this->createPrivateThread($this->tippin, $this->doe);
        $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.index'))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_view_paginated_threads()
    {
        $this->logCurrentRequest();
        $this->createPrivateThread($this->tippin, $this->doe);
        $this->createGroupThread($this->tippin);
        $thread = $this->createGroupThread($this->tippin);
        $this->createPrivateThread($this->tippin, $this->developers);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.page', [
            'thread' => $thread->id,
        ]))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function invalid_thread_id_not_found()
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.show', [
            'thread' => '123456-789',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function user_forbidden_to_view_thread_they_do_not_belong_to()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.show', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_view_individual_private_thread()
    {
        $this->logCurrentRequest('PRIVATE');
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.show', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $thread->id,
                'type' => 1,
                'type_verbose' => 'PRIVATE',
                'group' => false,
                'resources' => [
                    'recipient' => [
                        'provider_id' => $this->doe->getKey(),
                        'name' => 'John Doe',
                    ],
                ],
            ]);
    }

    /** @test */
    public function user_can_view_individual_group_thread()
    {
        $this->logCurrentRequest('GROUP');
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.show', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $thread->id,
                'type' => 2,
                'type_verbose' => 'GROUP',
                'group' => true,
            ]);
    }

    /** @test */
    public function unread_thread_is_unread()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.is.unread', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'unread' => true,
            ]);
    }

    /** @test */
    public function read_thread_is_not_unread()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->read()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.is.unread', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'unread' => false,
            ]);
    }

    /** @test */
    public function non_participant_forbidden_to_view_thread_with_loader()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.loader', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_view_thread_with_loader()
    {
        $this->logCurrentRequest();
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        Message::factory()->for($thread)->owner($this->tippin)->count(2)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.loader', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonStructure([
                'resources' => [
                    'latest_message',
                    'messages',
                    'participants',
                ],
            ]);
    }
}
