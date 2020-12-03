<?php

use RTippin\Messenger\Http\Controllers\Actions\RenderProviderAvatar;

/*
|--------------------------------------------------------------------------
| Messenger WEB provider avatar view route
|--------------------------------------------------------------------------
*/

//Images
Route::get('{alias}/{id}/{size}/{image}', RenderProviderAvatar::class)->name('avatar.render');