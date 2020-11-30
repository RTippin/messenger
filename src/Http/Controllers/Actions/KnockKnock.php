<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Threads\SendKnock;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;

class KnockKnock
{
    use AuthorizesRequests;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * KnockKnock constructor.
     *
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * Check if provider can knock in thread and
     * no timeout exist, then knock! 👊✊
     *
     * @param SendKnock $sendKnock
     * @param Thread $thread
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function __invoke(SendKnock $sendKnock, Thread $thread)
    {
        $this->authorize('sendKnock', $thread);

        $this->checkKnockTimeout($thread);

        return $sendKnock->execute($thread)
            ->getMessageResponse();

    }

    /**
     * @param Thread $thread
     * @throws AuthorizationException
     */
    private function checkKnockTimeout(Thread $thread)
    {
        if($thread->hasKnockTimeout())
        {
            throw new AuthorizationException("You may only knock at {$thread->name()} once every {$this->messenger->getKnockTimeout()} minutes.");
        }
    }
}