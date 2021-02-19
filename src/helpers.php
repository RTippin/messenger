<?php

use RTippin\Messenger\Messenger;

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
