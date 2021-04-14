<?php

use Illuminate\Support\Facades\Route;
use RTippin\Messenger\Http\Controllers\InviteController;

/*
|--------------------------------------------------------------------------
| Invitation join "public" api route. Separated so auth
| type middleware does not have to be included
|--------------------------------------------------------------------------
*/

Route::prefix('join/{invite:code}')->name('api.messenger.invites.')->group(function () {
    Route::get('/', [InviteController::class, 'show'])->name('join');
    Route::get('avatar/{size}/{image}', [InviteController::class, 'renderAvatar'])->name('avatar.render');
});
