<?php

namespace RTippin\Messenger\Facades;

use Illuminate\Support\Facades\Facade;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Contracts\MessengerProvider;

/**
 * @method static \RTippin\Messenger\Support\MessengerComposer to($entity)
 * @method static \RTippin\Messenger\Support\MessengerComposer from(MessengerProvider $provider)
 * @method static \RTippin\Messenger\Support\MessengerComposer silent()
 * @method static \RTippin\Messenger\Support\MessengerComposer getInstance()
 * @method static StoreMessage message(string $message, ?string $replyingToId = null, ?array $extra = null)
 *
 * @mixin \RTippin\Messenger\Support\MessengerComposer
 * @see \RTippin\Messenger\Support\MessengerComposer
 */
class MessengerComposer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \RTippin\Messenger\Support\MessengerComposer::class;
    }
}
