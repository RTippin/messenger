<?php

use Illuminate\Support\Facades\Route;
use RTippin\Messenger\Http\Controllers\ViewPortalController;

/*
|--------------------------------------------------------------------------
| Invitation join "public" web route. Separated so auth
| type middleware does not have to be included
|--------------------------------------------------------------------------
*/

Route::get('join/{invite}', [ViewPortalController::class, 'showJoinWithInvite'])->name('messenger.invites.join');
