<?php

use RTippin\Messenger\Broadcasting\CallChannel;
use RTippin\Messenger\Broadcasting\ProviderChannel;
use RTippin\Messenger\Broadcasting\ThreadChannel;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('{alias}.{id}', ProviderChannel::class);

Broadcast::channel('call.{call}.thread.{thread}', CallChannel::class);

Broadcast::channel('thread.{thread}', ThreadChannel::class);
