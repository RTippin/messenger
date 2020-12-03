<?php

use Illuminate\Support\Facades\Broadcast;
use RTippin\Messenger\Broadcasting\CallChannel;
use RTippin\Messenger\Broadcasting\ProviderChannel;
use RTippin\Messenger\Broadcasting\ThreadChannel;

/*
|--------------------------------------------------------------------------
| Messenger Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('{alias}.{id}', ProviderChannel::class);

Broadcast::channel('call.{call}.thread.{thread}', CallChannel::class);

Broadcast::channel('thread.{thread}', ThreadChannel::class);
