<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Tests\FeatureTestCase;

class GroupThreadsTest extends FeatureTestCase
{
    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.groups.index'))
            ->assertUnauthorized();

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Group',
        ])
            ->assertUnauthorized();
    }
}
