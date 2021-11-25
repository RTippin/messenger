<?php

namespace RTippin\Messenger\Support;

abstract class PackagedBot
{
    /**
     * @return string
     */
    abstract public static function name(): string;

    /**
     * @return string
     */
    abstract public static function description(): string;

    /**
     * @return array
     */
    abstract public static function installs(): array;

    /**
     * @return string|null
     */
    public static function avatar(): ?string
    {
        return null;
    }

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

    /**
     * @todo
     */
    public function install(): void
    {
        //
    }
}
