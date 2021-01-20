<?php

namespace RTippin\Messenger\Tests\stubs;

use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

class TestVideoBroker implements VideoDriver
{
    private bool $fail = false;

    public function create(Thread $thread, Call $call): bool
    {
        return ! $this->fail;
    }

    public function destroy(Call $call): bool
    {
        return ! $this->fail;
    }

    public function getRoomId(): ?string
    {
        return '123456';
    }

    public function getRoomPin(): ?string
    {
        return 'TEST-PIN';
    }

    public function getRoomSecret(): ?string
    {
        return 'TEST-SECRET';
    }

    public function getExtraPayload(): ?string
    {
        return 'TEST-EXTRA-PAYLOAD';
    }
}
