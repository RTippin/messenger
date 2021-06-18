<?php

use Illuminate\Support\Facades\Route;
use RTippin\Messenger\Http\Controllers\Actions\AvailableBotHandlers;
use RTippin\Messenger\Http\Controllers\Actions\CallHeartbeat;
use RTippin\Messenger\Http\Controllers\Actions\DemoteAdmin;
use RTippin\Messenger\Http\Controllers\Actions\DownloadMessageAudio;
use RTippin\Messenger\Http\Controllers\Actions\DownloadMessageFile;
use RTippin\Messenger\Http\Controllers\Actions\EndCall;
use RTippin\Messenger\Http\Controllers\Actions\FilterAddParticipants;
use RTippin\Messenger\Http\Controllers\Actions\FindRecipientThread;
use RTippin\Messenger\Http\Controllers\Actions\IgnoreCall;
use RTippin\Messenger\Http\Controllers\Actions\IsThreadUnread;
use RTippin\Messenger\Http\Controllers\Actions\JoinCall;
use RTippin\Messenger\Http\Controllers\Actions\JoinGroupInvite;
use RTippin\Messenger\Http\Controllers\Actions\KnockKnock;
use RTippin\Messenger\Http\Controllers\Actions\LeaveCall;
use RTippin\Messenger\Http\Controllers\Actions\MarkThreadRead;
use RTippin\Messenger\Http\Controllers\Actions\MuteThread;
use RTippin\Messenger\Http\Controllers\Actions\PrivateThreadApproval;
use RTippin\Messenger\Http\Controllers\Actions\PromoteAdmin;
use RTippin\Messenger\Http\Controllers\Actions\RenderBotAvatar;
use RTippin\Messenger\Http\Controllers\Actions\RenderGroupAvatar;
use RTippin\Messenger\Http\Controllers\Actions\RenderMessageImage;
use RTippin\Messenger\Http\Controllers\Actions\RenderProviderAvatar;
use RTippin\Messenger\Http\Controllers\Actions\Search;
use RTippin\Messenger\Http\Controllers\Actions\StatusHeartbeat;
use RTippin\Messenger\Http\Controllers\Actions\ThreadArchiveState;
use RTippin\Messenger\Http\Controllers\Actions\ThreadLoader;
use RTippin\Messenger\Http\Controllers\Actions\UnmuteThread;
use RTippin\Messenger\Http\Controllers\Actions\UnreadThreadsCount;
use RTippin\Messenger\Http\Controllers\AudioMessageController;
use RTippin\Messenger\Http\Controllers\BotActionController;
use RTippin\Messenger\Http\Controllers\BotController;
use RTippin\Messenger\Http\Controllers\CallController;
use RTippin\Messenger\Http\Controllers\CallParticipantController;
use RTippin\Messenger\Http\Controllers\DocumentMessageController;
use RTippin\Messenger\Http\Controllers\FriendController;
use RTippin\Messenger\Http\Controllers\GroupThreadController;
use RTippin\Messenger\Http\Controllers\ImageMessageController;
use RTippin\Messenger\Http\Controllers\InviteController;
use RTippin\Messenger\Http\Controllers\MessageController;
use RTippin\Messenger\Http\Controllers\MessageReactionController;
use RTippin\Messenger\Http\Controllers\MessengerController;
use RTippin\Messenger\Http\Controllers\ParticipantController;
use RTippin\Messenger\Http\Controllers\PendingFriendController;
use RTippin\Messenger\Http\Controllers\PrivateThreadController;
use RTippin\Messenger\Http\Controllers\SentFriendController;
use RTippin\Messenger\Http\Controllers\SystemMessageController;
use RTippin\Messenger\Http\Controllers\ThreadController;

/*
|--------------------------------------------------------------------------
| Messenger API Routes
|--------------------------------------------------------------------------
*/

//Provider Avatars API render
if (config('messenger.routing.provider_avatar.enabled')) {
    Route::get(trim(config('messenger.routing.provider_avatar.prefix'), '/').'/{alias}/{id}/{size}/{image}', RenderProviderAvatar::class)->name('api.avatar.render');
}

Route::name('api.messenger.')->group(function () {
    //Messenger view service settings
    Route::get('/', [MessengerController::class, 'index'])->name('info');
    //Invitation join
    Route::post('join/{invite:code}', JoinGroupInvite::class)->name('invites.join.store');
    //Search
    Route::get('search/{query?}', Search::class)->name('search');
    //Friends routes
    Route::prefix('friends')->name('friends.')->group(function () {
        Route::apiResource('pending', PendingFriendController::class)->except('store');
        Route::apiResource('sent', SentFriendController::class)->except('update');
    });
    Route::apiResource('friends', FriendController::class)->except(['update', 'store']);
    //Base messenger routes
    Route::post('heartbeat', StatusHeartbeat::class)->name('heartbeat');
    Route::get('active-calls', [MessengerController::class, 'activeCalls'])->name('active.calls');
    Route::get('settings', [MessengerController::class, 'settings'])->name('settings');
    Route::put('settings', [MessengerController::class, 'updateSettings'])->name('settings.update');
    Route::post('avatar', [MessengerController::class, 'updateAvatar'])->name('avatar.update');
    Route::delete('avatar', [MessengerController::class, 'destroyAvatar'])->name('avatar.destroy');
    Route::get('unread-threads-count', UnreadThreadsCount::class)->name('unread.threads.count');
    Route::apiResource('groups', GroupThreadController::class)->only(['index', 'store']);
    Route::get('groups/page/{group}', [GroupThreadController::class, 'paginate'])->name('groups.page');
    Route::apiResource('privates', PrivateThreadController::class)->only(['index', 'store']);
    Route::get('privates/page/{private}', [PrivateThreadController::class, 'paginate'])->name('privates.page');
    Route::get('privates/recipient/{alias}/{id}', FindRecipientThread::class)->name('privates.locate');
    //Thread resources
    Route::prefix('threads/{thread}')->name('threads.')->group(function () {
        //Pagination
        Route::get('participants/page/{participant}', [ParticipantController::class, 'paginate'])->name('participants.page');
        Route::get('messages/page/{message}', [MessageController::class, 'paginate'])->name('messages.page');
        Route::get('messages/{message}/history', [MessageController::class, 'showEdits'])->name('messages.history');
        Route::get('calls/page/{call}', [CallController::class, 'paginate'])->name('calls.page');
        Route::get('logs/page/{log}', [SystemMessageController::class, 'paginate'])->name('logs.page');
        Route::get('images/page/{image}', [ImageMessageController::class, 'paginate'])->name('images.page');
        Route::get('documents/page/{document}', [DocumentMessageController::class, 'paginate'])->name('documents.page');
        Route::get('audio/page/{audio}', [AudioMessageController::class, 'paginate'])->name('audio.page');
        //Common
        Route::delete('messages/{message}/embeds', [MessageController::class, 'removeEmbeds'])->name('messages.embeds.destroy');
        Route::get('gallery/{message}/{size}/{image}', RenderMessageImage::class)->name('gallery.render');
        Route::get('files/{message}/{file}', DownloadMessageFile::class)->name('files.download');
        Route::get('audio/{message}/{audio}', DownloadMessageAudio::class)->name('audio.download');
        Route::get('load/{relations?}', ThreadLoader::class)->name('loader');
        Route::get('logs', [SystemMessageController::class, 'index'])->name('logs');
        Route::get('mark-read', MarkThreadRead::class)->name('mark.read');
        Route::get('is-unread', IsThreadUnread::class)->name('is.unread');
        Route::post('knock-knock', KnockKnock::class)->name('knock');
        Route::get('check-archive', ThreadArchiveState::class)->name('archive.check');
        Route::post('mute', MuteThread::class)->name('mute');
        Route::post('unmute', UnmuteThread::class)->name('unmute');
        //Groups
        Route::post('leave', [GroupThreadController::class, 'leave'])->name('leave');
        Route::get('settings', [GroupThreadController::class, 'settings'])->name('settings');
        Route::put('settings', [GroupThreadController::class, 'updateSettings'])->name('settings.update');
        Route::post('avatar', [GroupThreadController::class, 'updateAvatar'])->name('avatar.update');
        Route::get('avatar/{size}/{image}', RenderGroupAvatar::class)->name('avatar.render');

        Route::get('add-participants', FilterAddParticipants::class)->name('add.participants');
        //Privates
        Route::post('approval', PrivateThreadApproval::class)->name('approval');
    });
    //main API resources
    Route::apiResource('threads', ThreadController::class)->except(['store', 'update']);
    Route::get('threads/page/{thread}', [ThreadController::class, 'paginate'])->name('threads.page');
    Route::apiResource('threads.participants', ParticipantController::class);
    Route::prefix('threads/{thread}/participants/{participant}')->name('threads.participants.')->group(function () {
        Route::post('promote', PromoteAdmin::class)->name('promote');
        Route::post('demote', DemoteAdmin::class)->name('demote');
    });
    Route::apiResource('threads.bots', BotController::class);
    Route::apiResource('threads.bots.actions', BotActionController::class);
    Route::prefix('threads/{thread}/bots/{bot}')->name('threads.bots.')->group(function () {
        Route::get('add-handlers', AvailableBotHandlers::class)->name('handlers');
        Route::get('avatar/{size}/{image}', RenderBotAvatar::class)->name('avatar.render');
        Route::post('avatar', [BotController::class, 'storeAvatar'])->name('avatar.store');
        Route::delete('avatar', [BotController::class, 'destroyAvatar'])->name('avatar.destroy');
    });
    Route::apiResource('threads.messages', MessageController::class);
    Route::apiResource('threads.messages.reactions', MessageReactionController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('threads.images', ImageMessageController::class)->only(['index', 'store']);
    Route::apiResource('threads.documents', DocumentMessageController::class)->only(['index', 'store']);
    Route::apiResource('threads.audio', AudioMessageController::class)->only(['index', 'store']);
    Route::apiResource('threads.invites', InviteController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('threads.calls', CallController::class)->except(['update', 'destroy']);
    Route::prefix('threads/{thread}/calls/{call}')->name('threads.calls.')->group(function () {
        Route::post('join', JoinCall::class)->name('join');
        Route::post('leave', LeaveCall::class)->name('leave');
        Route::post('end', EndCall::class)->name('end');
        Route::post('ignore', IgnoreCall::class)->name('ignore');
        Route::get('heartbeat', CallHeartbeat::class)->name('heartbeat');
    });
    Route::apiResource('threads.calls.participants', CallParticipantController::class)->except(['store', 'destroy']);
});
