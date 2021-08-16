<?php

use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Support\MessengerComposer;

if (! function_exists('messenger')) {
    /**
     * Return the Messenger singleton instance.
     *
     * @return Messenger
     */
    function messenger(): Messenger
    {
        return app(Messenger::class);
    }
}

if (! function_exists('broadcaster')) {
    /**
     * Return a new instance of the bound broadcast driver.
     *
     * @return BroadcastDriver
     */
    function broadcaster(): BroadcastDriver
    {
        return app(BroadcastDriver::class);
    }
}

if (! function_exists('bots')) {
    /**
     * Return the MessengerBots singleton instance.
     *
     * @return MessengerBots
     */
    function bots(): MessengerBots
    {
        return app(MessengerBots::class);
    }
}

if (! function_exists('messengerComposer')) {
    /**
     * Return a new MessengerComposer instance.
     *
     * @return MessengerComposer
     */
    function messengerComposer(): MessengerComposer
    {
        return app(MessengerComposer::class);
    }
}
