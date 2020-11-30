<?php

namespace RTippin\Messenger\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;

class MessageRepository
{
    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * MessageRepository constructor.
     *
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @return Builder
     */
    public function getProviderMessagesBuilder(): Builder
    {
        return Message::nonSystem()
            ->where('owner_id', '=', $this->messenger->getProviderId())
            ->where('owner_type', '=', $this->messenger->getProviderClass());
    }

    /**
     * @param Thread $thread
     * @return Collection
     */
    public function getThreadMessagesIndex(Thread $thread)
    {
        return $thread->messages()
            ->latest()
            ->limit($this->messenger->getMessagesIndexCount())
            ->with('owner')
            ->get();
    }

    /**
     * @param Thread $thread
     * @param Message $message
     * @return Collection
     */
    public function getThreadMessagesPage(Thread $thread, Message $message)
    {
        return $thread->messages()
            ->latest()
            ->with('owner')
            ->where('created_at', '<=', $message->created_at)
            ->where('id', '!=', $message->id)
            ->limit($this->messenger->getMessagesPageCount())
            ->get();
    }
}