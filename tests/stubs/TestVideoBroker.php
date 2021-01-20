<?php

namespace RTippin\Messenger\Tests\stubs;

use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

class TestVideoBroker implements VideoDriver
{
    /**
     * @inheritDoc
     */
    public function create(Thread $thread, Call $call): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function destroy(Call $call): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getRoomId(): ?string
    {
        return '123456';
    }

    /**
     * @inheritDoc
     */
    public function getRoomPin(): ?string
    {
        return 'TEST-PIN';
    }

    /**
     * @inheritDoc
     */
    public function getRoomSecret(): ?string
    {
        return 'TEST-SECRET';
    }

    /**
     * @inheritDoc
     */
    public function getExtraPayload(): ?string
    {
        return 'TEST-EXTRA-PAYLOAD';
    }
}
