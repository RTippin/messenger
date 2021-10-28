<?php

namespace RTippin\Messenger\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;

class MessagePolicy
{
    use HandlesAuthorization;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * MessagePolicy constructor.
     *
     * @param  Messenger  $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * Determine whether the provider can view messages.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function viewAny($user, Thread $thread): Response
    {
        return $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view messages.');
    }

    /**
     * Determine whether the provider can view the message.
     *
     * @param $user
     * @param  Message  $message
     * @param  Thread  $thread
     * @return Response
     */
    public function view($user, Message $message, Thread $thread): Response
    {
        return $thread->id === $message->thread_id
        && $thread->hasCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to view message.');
    }

    /**
     * Determine whether the provider can view the message edits.
     *
     * @param $user
     * @param  Message  $message
     * @param  Thread  $thread
     * @return Response
     */
    public function viewEdits($user, Message $message, Thread $thread): Response
    {
        return $thread->id === $message->thread_id
        && $thread->hasCurrentProvider()
        && $this->messenger->isMessageEditsEnabled()
        && $this->messenger->isMessageEditsViewEnabled()
        && $message->isEdited()
            ? $this->allow()
            : $this->deny('Not authorized to view message edits.');
    }

    /**
     * Determine whether the provider can create a message.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function create($user, Thread $thread): Response
    {
        return $thread->canMessage()
            ? $this->allow()
            : $this->deny('Not authorized to send messages.');
    }

    /**
     * Determine whether the provider can create a document message.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function createDocument($user, Thread $thread): Response
    {
        return $this->messenger->isMessageDocumentUploadEnabled() && $thread->canMessage()
            ? $this->allow()
            : $this->deny('Not authorized to upload document messages.');
    }

    /**
     * Determine whether the provider can create an audio message.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function createAudio($user, Thread $thread): Response
    {
        return $this->messenger->isMessageAudioUploadEnabled() && $thread->canMessage()
            ? $this->allow()
            : $this->deny('Not authorized to upload audio messages.');
    }

    /**
     * Determine whether the provider can create an image message.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function createImage($user, Thread $thread): Response
    {
        return $this->messenger->isMessageImageUploadEnabled() && $thread->canMessage()
            ? $this->allow()
            : $this->deny('Not authorized to upload image messages.');
    }

    /**
     * Determine whether the provider can create a video message.
     *
     * @param $user
     * @param  Thread  $thread
     * @return Response
     */
    public function createVideo($user, Thread $thread): Response
    {
        return $this->messenger->isMessageVideoUploadEnabled() && $thread->canMessage()
            ? $this->allow()
            : $this->deny('Not authorized to upload video messages.');
    }

    /**
     * Determine whether the provider can edit the message.
     *
     * @param $user
     * @param  Message  $message
     * @param  Thread  $thread
     * @return Response
     */
    public function update($user, Message $message, Thread $thread): Response
    {
        return $thread->id === $message->thread_id
        && ! $thread->isLocked()
        && $message->isText()
        && $message->isOwnedByCurrentProvider()
            ? $this->allow()
            : $this->deny('Not authorized to edit message.');
    }

    /**
     * Determine whether the provider can remove embeds from the message.
     *
     * @param $user
     * @param  Message  $message
     * @param  Thread  $thread
     * @return Response
     */
    public function removeEmbeds($user, Message $message, Thread $thread): Response
    {
        return $thread->id === $message->thread_id
        && ! $thread->isLocked()
        && $message->showEmbeds()
        && ($message->isOwnedByCurrentProvider()
            || $thread->isAdmin())
            ? $this->allow()
            : $this->deny('Not authorized to remove message embeds.');
    }

    /**
     * Determine whether the provider can delete the message.
     *
     * @param $user
     * @param  Message  $message
     * @param  Thread  $thread
     * @return Response
     */
    public function delete($user, Message $message, Thread $thread): Response
    {
        return $thread->id === $message->thread_id
        && ! $thread->isLocked()
        && $message->notSystemMessage()
        && ($message->isOwnedByCurrentProvider()
            || $thread->isAdmin())
            ? $this->allow()
            : $this->deny('Not authorized to remove message.');
    }
}
