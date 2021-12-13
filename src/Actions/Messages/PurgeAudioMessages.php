<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Services\FileService;

class PurgeAudioMessages extends BaseMessengerAction
{
    /**
     * @var FileService
     */
    private FileService $fileService;

    /**
     * PurgeAudioMessages constructor.
     *
     * @param  FileService  $fileService
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Loop through the collection of audio messages and remove
     * the file from storage, then force delete message itself
     * from database.
     *
     * @param  Collection  $audioMessages
     * @return $this
     */
    public function execute(Collection $audioMessages): self
    {
        $audioMessages->each(fn (Message $audio) => $this->purge($audio));

        return $this;
    }

    /**
     * @param  Message  $audio
     * @return void
     */
    private function purge(Message $audio): void
    {
        $this->destroyAudio($audio);

        $this->destroyMessage($audio);
    }

    /**
     * @param  Message  $audio
     * @return void
     */
    private function destroyAudio(Message $audio): void
    {
        $this->fileService
            ->setDisk($audio->getStorageDisk())
            ->destroy($audio->getAudioPath());
    }

    /**
     * @param  Message  $audio
     * @return void
     */
    private function destroyMessage(Message $audio): void
    {
        $audio->forceDelete();
    }
}
