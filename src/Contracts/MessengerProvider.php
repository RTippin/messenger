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
    public function name();

    /**
     * @param string $size
     * @param bool $api
     * @return string|null
     */
    public function getAvatarRoute(string $size = 'sm', $api = false);

    /**
     * @return string|null
     */
    public function getRoute();

    /**
     * @return int
     */
    public function onlineStatus();

    /**
     * @return string
     */
    public function onlineStatusVerbose();
}