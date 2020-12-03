<?php

use RTippin\Messenger\Http\Controllers\Actions\DownloadMessageFile;
use RTippin\Messenger\Http\Controllers\Actions\RenderGroupAvatar;
use RTippin\Messenger\Http\Controllers\Actions\RenderMessageImage;
use RTippin\Messenger\Http\Controllers\Actions\RenderProviderAvatar;
use RTippin\Messenger\Http\Controllers\ViewPortalController;

/*
|--------------------------------------------------------------------------
| WEB Routes
|--------------------------------------------------------------------------
|
| Here is where you can register WEB routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "web" middleware group.
|
*/

//Images
Route::get('images/{alias}/{id}/{size}/{image}', RenderProviderAvatar::class)
    ->name('avatar.render')
    ->middleware('cache.headers:public, max-age=86400;');

//Messenger Invite Join
Route::get('join/{invite}', [ViewPortalController::class, 'showJoinWithInvite'])->name('messenger.invites.join');

//Authed routes
Route::middleware(['auth', 'messenger.provider'])->group(function(){
    Route::prefix('messenger')->name('messenger.')->group(function(){
        Route::get('/', [ViewPortalController::class, 'index'])->name('portal');
        Route::get('{thread}', [ViewPortalController::class, 'showThread'])->name('show');
        Route::get('/recipient/{alias}/{id}', [ViewPortalController::class, 'showCreatePrivate'])->name('private.create');
        Route::prefix('threads/{thread}')->name('threads.')->group(function(){
            Route::get('avatar/{size}/{image}', RenderGroupAvatar::class)->name('avatar.render');
            Route::get('gallery/{message}/{size}/{image}', RenderMessageImage::class)->name('gallery.render');
            Route::get('files/{message}/{file}', DownloadMessageFile::class)->name('files.download');
            Route::get('calls/{call}', [ViewPortalController::class, 'showVideoCall']);
        });
    });
});