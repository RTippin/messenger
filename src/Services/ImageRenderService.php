<?php

namespace RTippin\Messenger\Services;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Response;
use Illuminate\Routing\ResponseFactory;
use Intervention\Image\ImageManager;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageRenderService
{
    /**
     * Extensions we do not want to send through to intervention.
     */
    const IGNORED_EXTENSIONS = [
        'gif',
        'svg',
        'webp',
    ];

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var MessengerBots
     */
    private MessengerBots $bots;

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
     * @param  Messenger  $messenger
     * @param  MessengerBots  $bots
     * @param  FilesystemManager  $filesystemManager
     * @param  ResponseFactory  $responseFactory
     * @param  ImageManager  $imageManager
     */
    public function __construct(Messenger $messenger,
                                MessengerBots $bots,
                                FilesystemManager $filesystemManager,
                                ResponseFactory $responseFactory,
                                ImageManager $imageManager)
    {
        $this->messenger = $messenger;
        $this->bots = $bots;
        $this->filesystemManager = $filesystemManager;
        $this->responseFactory = $responseFactory;
        $this->imageManager = $imageManager;
    }

    /**
     * @param  string  $alias
     * @param  string  $id
     * @param  string  $size
     * @param  string  $image
     * @return StreamedResponse|BinaryFileResponse
     *
     * @throws FileNotFoundException
     */
    public function renderProviderAvatar(string $alias,
                                         string $id,
                                         string $size,
                                         string $image)
    {
        $avatar = "{$this->messenger->getAvatarStorage('directory')}/$alias/$id/$image";

        $disk = $this->messenger->getAvatarStorage('disk');

        if (! $this->filesystemManager->disk($disk)->exists($avatar)) {
            return $this->renderDefaultImage($alias);
        }

        $extension = pathinfo($this->filesystemManager->disk($disk)->path($avatar), PATHINFO_EXTENSION);

        if ($this->shouldResize($extension) && $size !== 'lg') {
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
     * @param  Message  $message
     * @param  string  $size
     * @param  string  $fileNameChallenge
     * @return BinaryFileResponse|StreamedResponse
     *
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

        $extension = pathinfo($this->filesystemManager->disk($message->getStorageDisk())->path($message->getImagePath()), PATHINFO_EXTENSION);

        if ($this->shouldResize($extension) && $size !== 'lg') {
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
     * @param  Thread  $thread
     * @param  string  $size
     * @param  string  $fileNameChallenge
     * @return StreamedResponse|BinaryFileResponse
     *
     * @throws FileNotFoundException
     */
    public function renderGroupAvatar(Thread $thread,
                                      string $size,
                                      string $fileNameChallenge)
    {
        if (! $thread->isGroup()
            || is_null($thread->image)
            || $fileNameChallenge !== $thread->image) {
            return $this->renderDefaultImage('thread');
        }

        if (! $this->filesystemManager
            ->disk($thread->getStorageDisk())
            ->exists($thread->getAvatarPath())) {
            return $this->renderDefaultImage();
        }

        $extension = pathinfo($this->filesystemManager->disk($thread->getStorageDisk())->path($thread->getAvatarPath()), PATHINFO_EXTENSION);

        if ($this->shouldResize($extension) && $size !== 'lg') {
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
     * @param  Bot  $bot
     * @param  string  $size
     * @param  string  $fileNameChallenge
     * @return StreamedResponse|BinaryFileResponse
     *
     * @throws FileNotFoundException
     */
    public function renderBotAvatar(Bot $bot,
                                    string $size,
                                    string $fileNameChallenge)
    {
        if ($fileNameChallenge !== $bot->avatar) {
            return $this->renderDefaultImage('bot');
        }

        if (! $this->filesystemManager
            ->disk($bot->getStorageDisk())
            ->exists($bot->getAvatarPath())) {
            return $this->renderDefaultImage('bot');
        }

        $extension = pathinfo($this->filesystemManager->disk($bot->getStorageDisk())->path($bot->getAvatarPath()), PATHINFO_EXTENSION);

        if ($this->shouldResize($extension) && $size !== 'lg') {
            return $this->renderImageSize(
                $this->filesystemManager
                    ->disk($bot->getStorageDisk())
                    ->get($bot->getAvatarPath()),
                $size
            );
        }

        return $this->filesystemManager
            ->disk($bot->getStorageDisk())
            ->response($bot->getAvatarPath());
    }

    /**
     * @param  string  $alias
     * @param  string  $size
     * @return BinaryFileResponse|Response
     */
    public function renderPackagedBotAvatar(string $alias, string $size)
    {
        if (! $this->bots->isValidPackagedBot($alias)) {
            return $this->renderDefaultImage('bot');
        }

        $package = $this->bots->getPackagedBot($alias);

        if (! $package->shouldInstallAvatar) {
            return $this->renderDefaultImage('bot');
        }

        $extension = pathinfo($package->avatar, PATHINFO_EXTENSION);

        if ($this->shouldResize($extension) && $size !== 'lg') {
            return $this->renderImageSize(
                file_get_contents($package->avatar),
                $size
            );
        }

        return $this->responseFactory->file($package->avatar);
    }

    /**
     * @param  string|null  $alias
     * @return BinaryFileResponse
     */
    private function renderDefaultImage(?string $alias = null): BinaryFileResponse
    {
        switch ($alias) {
            case 'ghost':
                $default = $this->messenger->getDefaultGhostAvatar();
            break;
            case 'bot':
                $default = $this->messenger->getDefaultBotAvatar();
            break;
            case 'thread':
                $default = $this->messenger->getDefaultThreadAvatar();
            break;
            default: $default = is_null($alias)
                ? null
                : $this->messenger->getProviderDefaultAvatarPath($alias);
        }

        if (! is_null($default) && file_exists($default)) {
            return $this->responseFactory->file($default);
        }

        return $this->responseFactory->file($this->messenger->getDefaultNotFoundImage());
    }

    /**
     * @param  string  $file
     * @param  string  $size
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

    /**
     * @param  string  $extension
     * @return bool
     */
    private function shouldResize(string $extension): bool
    {
        return ! in_array($extension, self::IGNORED_EXTENSIONS);
    }
}
