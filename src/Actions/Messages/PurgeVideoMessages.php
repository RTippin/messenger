<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Services\FileService;

class PurgeVideoMessages extends BaseMessengerAction
{
    /**
     * @var FileService
     */
    private FileService $fileService;

    /**
     * PurgeVideoMessages constructor.
     *
     * @param  FileService  $fileService
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Loop through the collection of video messages and remove
     * the file from storage, then force delete message itself
     * from database.
     *
     * @param  Collection  $videoMessages
     * @return $this
     */
    public function execute(Collection $videoMessages): self
    {
        $videoMessages->each(fn (Message $video) => $this->purge($video));

        return $this;
    }

    /**
     * @param  Message  $audio
     * @return void
     */
    private function purge(Message $audio): void
    {
        $this->destroyVideo($audio);

        $this->destroyMessage($audio);
    }

    /**
     * @param  Message  $video
     * @return void
     */
    private function destroyVideo(Message $video): void
    {
        $this->fileService
            ->setDisk($video->getStorageDisk())
            ->destroy($video->getVideoPath());
    }

    /**
     * @param  Message  $video
     * @return void
     */
    private function destroyMessage(Message $video): void
    {
        $video->forceDelete();
    }
}
