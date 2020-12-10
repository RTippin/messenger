<?php

use RTippin\Messenger\Http\Controllers\InviteController;

/*
|--------------------------------------------------------------------------
| Invitation join "public" api route. Separated so auth
| type middleware does not have to be included
|--------------------------------------------------------------------------
*/

Route::prefix('join/{invite:code}')->name('api.messenger.')->group(function () {
    Route::get('/', [InviteController::class, 'show'])->name('invites.join');
});
