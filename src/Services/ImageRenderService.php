<?php

namespace RTippin\Messenger\Services;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Response;
use Illuminate\Routing\ResponseFactory;
use Intervention\Image\ImageManager;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageRenderService
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var FilesystemManager
     */
    private FilesystemManager $filesystemManager;

    /**
     * @var ResponseFactory
     */
    private ResponseFactory $responseFactory;

    /**
     * @var ImageManager
     */
    private ImageManager $imageManager;

    /**
     * ImageRenderService constructor.
     *
     * @param Messenger $messenger
     * @param FilesystemManager $filesystemManager
     * @param ResponseFactory $responseFactory
     * @param ImageManager $imageManager
     */
    public function __construct(Messenger $messenger,
                                FilesystemManager $filesystemManager,
                                ResponseFactory $responseFactory,
                                ImageManager $imageManager)
    {
        $this->messenger = $messenger;
        $this->filesystemManager = $filesystemManager;
        $this->responseFactory = $responseFactory;
        $this->imageManager = $imageManager;
    }

    /**
     * @param string $alias
     * @param string $id
     * @param string $size
     * @param string $image
     * @return StreamedResponse|BinaryFileResponse
     * @throws FileNotFoundException
     */
    public function renderProviderAvatar(string $alias,
                                         string $id,
                                         string $size,
                                         string $image)
    {
        $avatar = "{$this->messenger->getAvatarStorage('directory')}/{$alias}/{$id}/{$image}";

        $disk = $this->messenger->getAvatarStorage('disk');

        if (! $this->filesystemManager->disk($disk)->exists($avatar)) {
            return $this->renderDefaultImage($alias);
        }

        if (pathinfo($this->filesystemManager->disk($disk)->path($avatar), PATHINFO_EXTENSION) !== 'gif'
            && $size !== 'lg') {
            return $this->renderImageSize(
                $this->filesystemManager->disk($disk)->get($avatar),
                $size
            );
        }

        return $this->filesystemManager
            ->disk($disk)
            ->response($avatar);
    }

    /**
     * @param Message $message
     * @param string $size
     * @param string $fileNameChallenge
     * @return BinaryFileResponse|StreamedResponse
     * @throws FileNotFoundException
     */
    public function renderMessageImage(Message $message,
                                       string $size,
                                       string $fileNameChallenge)
    {
        if (! $message->isImage()
            || $fileNameChallenge !== $message->body
            || ! $this->filesystemManager
                ->disk($message->getStorageDisk())
                ->exists($message->getImagePath())) {
            return $this->renderDefaultImage();
        }

        if (pathinfo($this->filesystemManager->disk($message->getStorageDisk())->path($message->getImagePath()), PATHINFO_EXTENSION) !== 'gif'
            && $size !== 'lg') {
            return $this->renderImageSize(
                $this->filesystemManager
                    ->disk($message->getStorageDisk())
                    ->get($message->getImagePath()),
                $size
            );
        }

        return $this->filesystemManager
            ->disk($message->getStorageDisk())
            ->response($message->getImagePath());
    }

    /**
     * @param Thread $thread
     * @param string $size
     * @param string $fileNameChallenge
     * @return StreamedResponse|BinaryFileResponse
     * @throws FileNotFoundException
     */
    public function renderGroupAvatar(Thread $thread,
                                      string $size,
                                      string $fileNameChallenge)
    {
        if (! $thread->isGroup()
            || $fileNameChallenge !== $thread->image) {
            return $this->renderDefaultImage();
        }

        if (in_array($thread->image, Definitions::DefaultGroupAvatars)) {
            return $this->responseFactory->file(
                $this->messenger->getDefaultThreadAvatars($thread->image)
            );
        }

        if (! $this->filesystemManager
            ->disk($thread->getStorageDisk())
            ->exists($thread->getAvatarPath())) {
            return $this->renderDefaultImage();
        }

        if (pathinfo($this->filesystemManager->disk($thread->getStorageDisk())->path($thread->getAvatarPath()), PATHINFO_EXTENSION) !== 'gif'
            && $size !== 'lg') {
            return $this->renderImageSize(
                $this->filesystemManager
                    ->disk($thread->getStorageDisk())
                    ->get($thread->getAvatarPath()),
                $size
            );
        }

        return $this->filesystemManager
            ->disk($thread->getStorageDisk())
            ->response($thread->getAvatarPath());
    }

    /**
     * @param string|null $alias
     * @return BinaryFileResponse|Response
     */
    private function renderDefaultImage($alias = null)
    {
        $default = $alias
            ? $this->messenger->getProviderDefaultAvatarPath($alias)
            : null;

        if ($default && file_exists($default)) {
            return $this->responseFactory->file($default);
        }

        return $this->responseFactory->file($this->messenger->getDefaultNotFoundImage());
    }

    /**
     * @param string $file
     * @param string $size
     * @return BinaryFileResponse|Response
     */
    private function renderImageSize(string $file, string $size)
    {
        try {
            $width = 150;
            $height = 150;

            if ($size === 'md') {
                $width = 300;
                $height = 300;
            }

            ($this->imageManager->make($file)->width() > $this->imageManager->make($file)->height())
                ? $width = null
                : $height = null;

            $resize = $this->imageManager->cache(function ($image) use ($file, $width, $height) {
                return $image->make($file)->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }, 120);

            return $this->imageManager->make($resize)->response();
        } catch (Exception $e) {
            report($e);
        }

        return $this->renderDefaultImage();
    }
}
