<?php

use RTippin\Messenger\Http\Controllers\Actions\RenderProviderAvatar;

/*
|--------------------------------------------------------------------------
| Messenger WEB provider avatar view route
|--------------------------------------------------------------------------
*/

//Provider Avatars Render
Route::get('{alias}/{id}/{size}/{image}', RenderProviderAvatar::class)->name('avatar.render');
