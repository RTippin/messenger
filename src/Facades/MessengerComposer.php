<?php

namespace RTippin\Messenger\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use RTippin\Messenger\Actions\Messages\AddReaction;
use RTippin\Messenger\Actions\Messages\StoreAudioMessage;
use RTippin\Messenger\Actions\Messages\StoreDocumentMessage;
use RTippin\Messenger\Actions\Messages\StoreImageMessage;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Actions\Messages\StoreVideoMessage;
use RTippin\Messenger\Actions\Threads\MarkParticipantRead;
use RTippin\Messenger\Actions\Threads\SendKnock;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Message;

/**
 * @method static \RTippin\Messenger\Support\MessengerComposer to($entity)
 * @method static \RTippin\Messenger\Support\MessengerComposer from(MessengerProvider $provider)
 * @method static \RTippin\Messenger\Support\MessengerComposer silent()
 * @method static \RTippin\Messenger\Support\MessengerComposer emitTyping()
 * @method static \RTippin\Messenger\Support\MessengerComposer emitStopTyping()
 * @method static \RTippin\Messenger\Support\MessengerComposer emitRead(?Message $message = null)
 * @method static \RTippin\Messenger\Support\MessengerComposer getInstance()
 * @method static StoreMessage message(string $message, ?string $replyingToId = null, ?array $extra = null)
 * @method static StoreImageMessage image(UploadedFile $image, ?string $replyingToId = null, ?array $extra = null)
 * @method static StoreDocumentMessage document(UploadedFile $document, ?string $replyingToId = null, ?array $extra = null)
 * @method static StoreAudioMessage audio(UploadedFile $audio, ?string $replyingToId = null, ?array $extra = null)
 * @method static StoreVideoMessage video(UploadedFile $video, ?string $replyingToId = null, ?array $extra = null)
 * @method static AddReaction reaction(Message $message, string $reaction)
 * @method static SendKnock knock()
 * @method static MarkParticipantRead read()
 *
 * @mixin \RTippin\Messenger\Support\MessengerComposer
 *
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
