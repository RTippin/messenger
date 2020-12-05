<?php

namespace RTippin\Messenger\Contracts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Contracts\MessengerProvider
 *
 * @mixin Model|\Eloquent
 */
interface MessengerProvider
{
    /**
     * @return Carbon|string|null
     */
    public function lastActiveDateTime();

    /**
     * @return string
     */
    public function name(): string;

    /**
     * @param string $size
     * @return string|null
     */
    public function getAvatarRoute(string $size = 'sm'): ?string;

    /**
     * @param bool $full
     * @return string
     */
    public function slug($full = false): string;

    /**
     * @return int
     */
    public function onlineStatus(): int;

    /**
     * @return string
     */
    public function onlineStatusVerbose(): string;
}