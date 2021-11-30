<?php

namespace RTippin\Messenger\Support;

abstract class PackagedBot
{
    /**
     * @return array
     */
    abstract public static function getSettings(): array;

    /**
     * @return array
     */
    abstract public static function installs(): array;

    /**
     * @return int
     */
    public function cooldown(): int
    {
        return 0;
    }

    /**
     * @return bool
     */
    public function enabled(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function hideActions(): bool
    {
        return false;
    }
}
