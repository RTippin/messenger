<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Facades\Messenger;

class SystemFeaturesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'bots' => false,
            'calling' => Messenger::isCallingEnabled(),
            'invitations' => Messenger::isThreadInvitesEnabled(),
            'invitations_max' => Messenger::getThreadMaxInvitesCount(),
            'knocks' => Messenger::isKnockKnockEnabled(),
            'audio_messages' => Messenger::isMessageAudioUploadEnabled(),
            'document_messages' => Messenger::isMessageDocumentUploadEnabled(),
            'image_messages' => Messenger::isMessageImageUploadEnabled(),
            'message_edits' => Messenger::isMessageEditsEnabled(),
            'message_edits_view' => Messenger::isMessageEditsViewEnabled(),
            'message_reactions' => Messenger::isMessageReactionsEnabled(),
            'message_reactions_max' => Messenger::getMessageReactionsMax(),
            'thread_avatars' => Messenger::isThreadAvatarUploadEnabled(),
        ];
    }
}
