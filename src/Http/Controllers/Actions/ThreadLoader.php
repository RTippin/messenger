<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Threads\MarkParticipantRead;
use RTippin\Messenger\Http\Resources\ThreadResource;
use RTippin\Messenger\Models\Thread;

class ThreadLoader
{
    use AuthorizesRequests;

    /**
     * Default eager load relations on thread.
     */
    const LOAD = [
        'recentMessage.owner',
        'participants.owner',
        'activeCall.participants.owner',
    ];

    /**
     * Display the specified thread. Load relations / actions if specified in route.
     * Available flags : (mark-read|participants|messages).
     *
     * @param Thread $thread
     * @param null|string $relations
     * @return ThreadResource
     * @throws AuthorizationException
     */
    public function __invoke(Thread $thread, ?string $relations = null): ThreadResource
    {
        $this->authorize('view', $thread);

        if ($relations) {
            return $this->withRelations($thread, $relations);
        }

        return new ThreadResource($thread->load(self::LOAD));
    }

    /**
     * @param Thread $thread
     * @param null|string $relations
     * @return ThreadResource
     */
    private function withRelations(Thread $thread, ?string $relations): ThreadResource
    {
        $options = array_filter(
            explode('|', $relations)
        );

        if (in_array('mark-read', $options)) {
            $this->markRead($thread);
        }

        return new ThreadResource(
            $thread->load(self::LOAD),
            in_array('participants', $options),
            in_array('messages', $options),
            in_array('calls', $options)
        );
    }

    /**
     * @param Thread $thread
     */
    private function markRead(Thread $thread): void
    {
        app(MarkParticipantRead::class)->execute(
            $thread->currentParticipant(),
            $thread
        );
    }
}
