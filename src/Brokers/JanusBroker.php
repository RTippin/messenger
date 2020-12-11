<?php

namespace RTippin\Messenger\Brokers;

use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\Janus\VideoRoomService;

class JanusBroker implements VideoDriver
{
    /**
     * @var VideoRoomService
     */
    protected VideoRoomService $videoRoomService;

    /**
     * @var string|null
     */
    protected ?string $roomId = null;

    /**
     * @var string|null
     */
    protected ?string $roomPin = null;

    /**
     * @var string|null
     */
    protected ?string $roomSecret = null;

    /**
     * @var string|null
     */
    protected ?string $extraPayload = null;

    /**
     * JanusBroker constructor.
     *
     * @param VideoRoomService $videoRoomService
     */
    public function __construct(VideoRoomService $videoRoomService)
    {
        $this->videoRoomService = $videoRoomService;
    }

    /**
     * @inheritDoc
     */
    public function create(Thread $thread, Call $call): bool
    {
        $janus = $this->videoRoomService->create(
            $this->settings($thread)
        );

        if (count($janus)) {
            $this->roomId = $janus['room'];
            $this->roomPin = $janus['pin'];
            $this->roomSecret = $janus['secret'];

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function destroy(Call $call): bool
    {
        return $this->videoRoomService->destroy(
            $call->room_id,
            $call->room_secret
        );
    }

    /**
     * @inheritDoc
     */
    public function getRoomId(): ?string
    {
        return $this->roomId;
    }

    /**
     * @inheritDoc
     */
    public function getRoomPin(): ?string
    {
        return $this->roomPin;
    }

    /**
     * @inheritDoc
     */
    public function getRoomSecret(): ?string
    {
        return $this->roomSecret;
    }

    /**
     * @inheritDoc
     */
    public function getExtraPayload(): ?string
    {
        return $this->extraPayload;
    }

    /**
     * @param Thread $thread
     * @return array
     */
    protected function settings(Thread $thread): array
    {
        return [
            'description' => $thread->id,
            'publishers' => $this->publishersCount($thread),
            'bitrate' => $this->bitrate($thread),
        ];
    }

    /**
     * @param Thread $thread
     * @return int
     */
    protected function publishersCount(Thread $thread): int
    {
        return $thread->isGroup()
            ? $thread->participants()->count() + 6
            : 4;
    }

    /**
     * @param Thread $thread
     * @return int
     */
    protected function bitrate(Thread $thread): int
    {
        return $thread->isGroup()
            ? 600000
            : 1024000;
    }
}
