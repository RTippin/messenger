<?php

namespace RTippin\Messenger\Repositories;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\Helpers;

class AudioMessageRepository
{
    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * ImageMessageRepository constructor.
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
    public function getThreadAudioIndex(Thread $thread): Collection
    {
        return $thread->messages()
            ->audio()
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
    public function getThreadAudioPage(Thread $thread, Message $message): Collection
    {
        return $thread->messages()
            ->audio()
            ->latest()
            ->with('owner')
            ->where('created_at', '<=', Helpers::PrecisionTime($message->created_at))
            ->where('id', '!=', $message->id)
            ->limit($this->messenger->getMessagesPageCount())
            ->get();
    }
}
