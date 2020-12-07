<?php

namespace RTippin\Messenger\Repositories;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;

class DocumentMessageRepository
{
    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * DocumentMessageRepository constructor.
     *
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @param Thread $thread
     * @return Collection
     */
    public function getThreadDocumentsIndex(Thread $thread): Collection
    {
        return $thread->messages()
            ->document()
            ->latest()
            ->with('owner')
            ->limit($this->messenger->getMessagesIndexCount())
            ->get();
    }

    /**
     * @param Thread $thread
     * @param Message $message
     * @return Collection
     */
    public function getThreadDocumentsPage(Thread $thread, Message $message): Collection
    {
        return $thread->messages()
            ->document()
            ->latest()
            ->with('owner')
            ->where('created_at', '<=', $message->created_at)
            ->where('id', '!=', $message->id)
            ->limit($this->messenger->getMessagesPageCount())
            ->get();
    }
}