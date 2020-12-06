<?php

use Illuminate\Database\Schema\Blueprint;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Messenger;

if ( ! function_exists('messenger'))
{
    /**
     * @return Messenger;
     *
     * Return the active instance of the messenger system
     */
    function messenger()
    {
        return resolve(Messenger::class);
    }
}

if ( ! function_exists('messengerFriends'))
{
    /**
     * @return FriendDriver;
     *
     * Return the active instance of the messenger system
     */
    function messengerFriends()
    {
        return resolve(FriendDriver::class);
    }
}

if ( ! function_exists('messengerMorphType'))
{
    /**
     * Helper to set morph key type in migrations
     * @param string $column
     * @param Blueprint $table
     */
    function messengerMorphType(string $column, Blueprint $table)
    {
        if(config('messenger.provider_uuids'))
        {
            $table->uuidMorphs($column);
        }
        else
        {
            $table->numericMorphs($column);
        }
    }
}

if ( ! function_exists('messengerRoute'))
{
    /**
     * Generate the URL to a named route.
     *
     * @param  array|string  $name
     * @param  mixed  $parameters
     * @param  bool  $absolute
     * @return string|null
     */
    function messengerRoute(string $name, $parameters = null, bool $absolute = false)
    {
        try{
            return app('url')->route(
                $name,
                $parameters ? $parameters : [],
                $absolute
            );
        }catch (Exception $e){
            report($e);
        }
        return null;
    }
}