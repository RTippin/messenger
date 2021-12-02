<?php

use Illuminate\Support\Facades\Route;
use RTippin\Messenger\Http\Controllers\Actions\DownloadMessageAudio;
use RTippin\Messenger\Http\Controllers\Actions\DownloadMessageFile;
use RTippin\Messenger\Http\Controllers\Actions\DownloadMessageVideo;
use RTippin\Messenger\Http\Controllers\Actions\RenderBotAvatar;
use RTippin\Messenger\Http\Controllers\Actions\RenderGroupAvatar;
use RTippin\Messenger\Http\Controllers\Actions\RenderMessageImage;
use RTippin\Messenger\Http\Controllers\Actions\RenderPackagedBotAvatar;
use RTippin\Messenger\Http\Controllers\Actions\RenderProviderAvatar;
use RTippin\Messenger\Http\Controllers\InviteController;

/*
|--------------------------------------------------------------------------
| Messenger Asset Routes
|--------------------------------------------------------------------------
*/

Route::name('assets.messenger.')->group(function () {
    Route::prefix('threads/{thread}')->name('threads.')->group(function () {
        Route::get('avatar/{size}/{image}', RenderGroupAvatar::class)->name('avatar.render');
        Route::get('bots/{bot:id}/avatar/{size}/{image}', RenderBotAvatar::class)->name('bots.avatar.render');
        Route::get('gallery/{message:id}/{size}/{image}', RenderMessageImage::class)->name('gallery.render');
        Route::get('files/{message:id}/{file}', DownloadMessageFile::class)->name('files.download');
        Route::get('audio/{message:id}/{audio}', DownloadMessageAudio::class)->name('audio.download');
        Route::get('videos/{message:id}/{video}', DownloadMessageVideo::class)->name('videos.download');
    });
    Route::get('invites/{invite:code}/avatar/{size}/{image}', [InviteController::class, 'renderAvatar'])->name('invites.avatar.render');
    Route::get('provider/{alias}/{id}/{size}/{image}', RenderProviderAvatar::class)->name('provider.avatar.render');
    Route::get('bot-package/{size}/{alias}/{image?}', RenderPackagedBotAvatar::class)->name('bot-package.avatar.render');
});
