<?php

namespace RTippin\Messenger\Http\Collections;

use Exception;
use Illuminate\Http\Request;
use RTippin\Messenger\Http\Collections\Base\MessengerCollection;
use RTippin\Messenger\Http\Resources\InviteResource;
use RTippin\Messenger\Models\Thread;
use Throwable;

class InviteCollection extends MessengerCollection
{
    /**
     * InviteCollection constructor.
     *
     * @param $resource
     * @param Thread $thread
     */
    public function __construct($resource, Thread $thread)
    {
        parent::__construct($resource);

        $this->thread = $thread;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->safeTransformer(),
            'meta' => [
                'total' => $this->thread->invites()->valid()->count(),
                'max_allowed' => messenger()->getThreadMaxInvitesCount() ?: null,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function makeResource($invite): ?array
    {
        try {
            return (new InviteResource($invite))->resolve();
        } catch (Exception $e) {
            report($e);
        } catch (Throwable $t) {
            report($t);
        }

        return null;
    }
}
