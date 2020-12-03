<?php

use RTippin\Messenger\Http\Controllers\InviteController;

//Invitation join "public" route
Route::prefix('join/{invite:code}')->name('api.messenger.')->group(function(){
    Route::get('/', [InviteController::class, 'show'])->name('invite.public.join');
});