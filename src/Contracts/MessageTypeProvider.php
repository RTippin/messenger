<?php

namespace RTippin\Messenger\Contracts;

use RTippin\Messenger\Models\Message;

interface MessageTypeProvider
{
    public function getVerbose(): string;

    public function getCode(): int;

    public function isSystemType(): bool;

    public function getResourceData(Message $message): ?array;
}
