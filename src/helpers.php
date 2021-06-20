<?php

use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\MessengerBots;

if (! function_exists('messenger')) {
    /**
     * @return Messenger
     *
     * Return the active instance of the messenger system
     */
    function messenger(): Messenger
    {
        return app(Messenger::class);
    }
}

if (! function_exists('broadcaster')) {
    /**
     * @return BroadcastDriver
     *
     * Return the active instance of the messenger system
     */
    function broadcaster(): BroadcastDriver
    {
        return app(BroadcastDriver::class);
    }
}

if (! function_exists('bots')) {
    /**
     * @return MessengerBots
     *
     * Return the active instance of the messenger system
     */
    function bots(): MessengerBots
    {
        return app(MessengerBots::class);
    }
}
