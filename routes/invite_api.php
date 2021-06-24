<?php

use Illuminate\Support\Facades\Route;
use RTippin\Messenger\Http\Controllers\InviteController;

/*
|--------------------------------------------------------------------------
| Invitation join "public" api route. Separated so auth
| type middleware does not have to be included
|--------------------------------------------------------------------------
*/

Route::get('join/{invite:code}', [InviteController::class, 'show'])->name('api.messenger.invites.join');
