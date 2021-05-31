<?php

use Illuminate\Support\Facades\Route;
use RTippin\Messenger\Http\Controllers\Actions\DownloadMessageAudio;
use RTippin\Messenger\Http\Controllers\Actions\DownloadMessageFile;
use RTippin\Messenger\Http\Controllers\Actions\RenderBotAvatar;
use RTippin\Messenger\Http\Controllers\Actions\RenderGroupAvatar;
use RTippin\Messenger\Http\Controllers\Actions\RenderMessageImage;
use RTippin\Messenger\Http\Controllers\ViewPortalController;

/*
|--------------------------------------------------------------------------
| Messenger WEB Routes
|--------------------------------------------------------------------------
*/

Route::name('messenger.')->group(function () {
    Route::get('/', [ViewPortalController::class, 'index'])->name('portal');
    Route::get('{thread}', [ViewPortalController::class, 'showThread'])->name('show');
    Route::get('/recipient/{alias}/{id}', [ViewPortalController::class, 'showCreatePrivate'])->name('private.create');
    Route::prefix('threads/{thread}')->name('threads.')->group(function () {
        Route::get('avatar/{size}/{image}', RenderGroupAvatar::class)->name('avatar.render');
        Route::get('bots/{bot}/avatar/{size}/{image}', RenderBotAvatar::class)->name('bots.avatar.render');
        Route::get('gallery/{message}/{size}/{image}', RenderMessageImage::class)->name('gallery.render');
        Route::get('files/{message}/{file}', DownloadMessageFile::class)->name('files.download');
        Route::get('audio/{message}/{audio}', DownloadMessageAudio::class)->name('audio.download');
        Route::get('calls/{call}', [ViewPortalController::class, 'showVideoCall'])->name('show.call');
    });
});
