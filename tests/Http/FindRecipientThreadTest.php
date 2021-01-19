<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class FindRecipientThreadTest extends FeatureTestCase
{
    private Thread $private;

    private Thread $privateWithCompany;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    private MessengerProvider $developers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->developers = $this->companyDevelopers();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->privateWithCompany = $this->createPrivateThread($this->tippin, $this->developers);
    }

    /**
     * @test
     * @dataProvider locatorValidation
     * @param $alias
     * @param $id
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

    /** @test */
    public function private_thread_locator_returns_user_with_existing_thread_id()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'user',
            'id' => $this->doe->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => $this->private->id,
                'recipient' => [
                    'provider_id' => $this->doe->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'John Doe',
                ],
            ]);
    }

    /** @test */
    public function private_thread_locator_returns_company_with_existing_thread_id()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'company',
            'id' => $this->developers->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => $this->privateWithCompany->id,
                'recipient' => [
                    'provider_id' => $this->developers->getKey(),
                    'provider_alias' => 'company',
                    'name' => 'Developers',
                ],
            ]);
    }

    /** @test */
    public function private_thread_locator_returns_user_without_existing_thread_id()
    {
        $otherUser = $this->createJaneSmith();

        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'user',
            'id' => $otherUser->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => null,
                'recipient' => [
                    'provider_id' => $otherUser->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Jane Smith',
                ],
            ]);
    }

    /** @test */
    public function private_thread_locator_returns_company_without_existing_thread_id()
    {
        $otherCompany = $this->createSomeCompany();

        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'company',
            'id' => $otherCompany->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => null,
                'recipient' => [
                    'provider_id' => $otherCompany->getKey(),
                    'provider_alias' => 'company',
                    'name' => 'Some Company',
                ],
            ]);
    }

    public function locatorValidation(): array
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
