<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Tests\HttpTestCase;

class FindRecipientThreadTest extends HttpTestCase
{
    /** @test */
    public function private_thread_locator_returns_user_with_existing_thread_id()
    {
        $this->logCurrentRequest('EXISTING');
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'user',
            'id' => $this->doe->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => $thread->id,
            ]);
    }

    /** @test */
    public function private_thread_locator_returns_company_with_existing_thread_id()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->developers);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'company',
            'id' => $this->developers->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => $thread->id,
            ]);
    }

    /** @test */
    public function private_thread_locator_returns_user_without_existing_thread_id()
    {
        $this->logCurrentRequest('NON_EXISTING');
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'user',
            'id' => $this->doe->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => null,
                'recipient' => [
                    'provider_id' => $this->doe->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'John Doe',
                ],
            ]);
    }

    /** @test */
    public function private_thread_locator_returns_company_without_existing_thread_id()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'company',
            'id' => $this->developers->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => null,
                'recipient' => [
                    'provider_id' => $this->developers->getKey(),
                    'provider_alias' => 'company',
                    'name' => 'Developers',
                ],
            ]);
    }

    /**
     * @test
     *
     * @dataProvider locatorValidation
     *
     * @param  $alias
     * @param  $id
     */
    public function private_thread_locator_returns_not_found_on_invalid_id_or_alias($alias, $id)
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => $alias,
            'id' => $id,
        ]))
            ->assertNotFound();
    }

    public static function locatorValidation(): array
    {
        return [
            'Not found user INT ID' => ['user', 404],
            'Not found user UUID' => ['user', '123-456-789'],
            'Not found company INT ID' => ['company', 404],
            'Not found company UUID' => ['company', '123-456-789'],
            'Invalid alias with valid ID' => ['unknown', 1],
        ];
    }
}
