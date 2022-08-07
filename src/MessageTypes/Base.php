<?php

namespace RTippin\Messenger\MessageTypes;

use RTippin\Messenger\Contracts\MessageTypeProvider;
use RTippin\Messenger\Models\Message;

abstract class Base implements MessageTypeProvider
{
    protected bool $isSystem = false;

    protected string $verbose = 'NOT_IMPLEMENTED';

    protected int $code = -1;

    public function isSystemType(): bool
    {
        return $this->isSystem;
    }

    public function getVerbose(): string
    {
        return $this->verbose;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getResourceData(Message $message): ?array
    {
        return [];
    }
}
