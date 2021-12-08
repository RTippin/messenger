<?php

use Illuminate\Support\Facades\Route;
use RTippin\Messenger\Http\Controllers\Actions\AvailableBotHandlers;
use RTippin\Messenger\Http\Controllers\Actions\AvailableBotPackages;
use RTippin\Messenger\Http\Controllers\Actions\CallHeartbeat;
use RTippin\Messenger\Http\Controllers\Actions\DemoteAdmin;
use RTippin\Messenger\Http\Controllers\Actions\EndCall;
use RTippin\Messenger\Http\Controllers\Actions\FilterAddParticipants;
use RTippin\Messenger\Http\Controllers\Actions\FindRecipientThread;
use RTippin\Messenger\Http\Controllers\Actions\IgnoreCall;
use RTippin\Messenger\Http\Controllers\Actions\InstallBotPackage;
use RTippin\Messenger\Http\Controllers\Actions\IsThreadUnread;
use RTippin\Messenger\Http\Controllers\Actions\JoinCall;
use RTippin\Messenger\Http\Controllers\Actions\JoinGroupInvite;
use RTippin\Messenger\Http\Controllers\Actions\KnockKnock;
use RTippin\Messenger\Http\Controllers\Actions\LeaveCall;
use RTippin\Messenger\Http\Controllers\Actions\MarkThreadRead;
use RTippin\Messenger\Http\Controllers\Actions\MuteThread;
use RTippin\Messenger\Http\Controllers\Actions\PrivateThreadApproval;
use RTippin\Messenger\Http\Controllers\Actions\PromoteAdmin;
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
use RTippin\Messenger\Http\Controllers\VideoMessageController;

/*
|--------------------------------------------------------------------------
| Messenger API Routes
|--------------------------------------------------------------------------
*/

Route::name('api.messenger.')->group(function () {
    //Messenger view service settings
    Route::get('/', [MessengerController::class, 'index'])->name('info');
    //Invitation join
    Route::post('join/{invite:code}', JoinGroupInvite::class)->name('invites.join.store');
    //Search
    Route::get('search/{query?}', Search::class)->name('search');
    //Friend routes
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
        Route::get('participants/page/{participant:id}', [ParticipantController::class, 'paginate'])->name('participants.page');
        Route::get('messages/page/{message:id}', [MessageController::class, 'paginate'])->name('messages.page');
        Route::get('messages/{message:id}/history', [MessageController::class, 'showEdits'])->name('messages.history');
        Route::get('calls/page/{call:id}', [CallController::class, 'paginate'])->name('calls.page');
        Route::get('logs/page/{log:id}', [SystemMessageController::class, 'paginate'])->name('logs.page');
        Route::get('images/page/{image:id}', [ImageMessageController::class, 'paginate'])->name('images.page');
        Route::get('documents/page/{document:id}', [DocumentMessageController::class, 'paginate'])->name('documents.page');
        Route::get('audio/page/{audio:id}', [AudioMessageController::class, 'paginate'])->name('audio.page');
        Route::get('videos/page/{video:id}', [VideoMessageController::class, 'paginate'])->name('videos.page');
        //Common
        Route::delete('messages/{message:id}/embeds', [MessageController::class, 'removeEmbeds'])->name('messages.embeds.destroy');
        //TODO v2 remove {relations?}
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
        Route::post('avatar', [GroupThreadController::class, 'storeAvatar'])->name('avatar.store');
        Route::delete('avatar', [GroupThreadController::class, 'destroyAvatar'])->name('avatar.destroy');
        Route::get('add-participants', FilterAddParticipants::class)->name('add.participants');
        //Privates
        Route::post('approval', PrivateThreadApproval::class)->name('approval');
    });
    //main API resources
    Route::apiResource('threads', ThreadController::class)->except(['store', 'update']);
    Route::get('threads/page/{thread}', [ThreadController::class, 'paginate'])->name('threads.page');
    Route::apiResource('threads.participants', ParticipantController::class)->scoped();
    Route::prefix('threads/{thread}/participants/{participant:id}')->name('threads.participants.')->group(function () {
        Route::post('promote', PromoteAdmin::class)->name('promote');
        Route::post('demote', DemoteAdmin::class)->name('demote');
    });
    Route::prefix('threads/{thread}/bots/packages')->name('threads.bots.packages.')->group(function () {
        Route::get('/', AvailableBotPackages::class)->name('index');
        Route::post('/', InstallBotPackage::class)->name('store');
    });
    Route::apiResource('threads.bots', BotController::class)->scoped();
    Route::apiResource('threads.bots.actions', BotActionController::class)->scoped();
    Route::prefix('threads/{thread}/bots/{bot:id}')->name('threads.bots.')->group(function () {
        Route::get('add-handlers', AvailableBotHandlers::class)->name('handlers');
        Route::post('avatar', [BotController::class, 'storeAvatar'])->name('avatar.store');
        Route::delete('avatar', [BotController::class, 'destroyAvatar'])->name('avatar.destroy');
    });
    Route::apiResource('threads.messages', MessageController::class)->scoped();
    Route::apiResource('threads.messages.reactions', MessageReactionController::class)->scoped()->only(['index', 'store', 'destroy']);
    Route::apiResource('threads.images', ImageMessageController::class)->scoped()->only(['index', 'store']);
    Route::apiResource('threads.documents', DocumentMessageController::class)->scoped()->only(['index', 'store']);
    Route::apiResource('threads.audio', AudioMessageController::class)->scoped()->only(['index', 'store']);
    Route::apiResource('threads.videos', VideoMessageController::class)->scoped()->only(['index', 'store']);
    Route::apiResource('threads.invites', InviteController::class)->scoped()->only(['index', 'store', 'destroy']);
    Route::apiResource('threads.calls', CallController::class)->scoped()->except(['update', 'destroy']);
    Route::prefix('threads/{thread}/calls/{call:id}')->name('threads.calls.')->group(function () {
        Route::post('join', JoinCall::class)->name('join');
        Route::post('leave', LeaveCall::class)->name('leave');
        Route::post('end', EndCall::class)->name('end');
        Route::post('ignore', IgnoreCall::class)->name('ignore');
        Route::get('heartbeat', CallHeartbeat::class)->name('heartbeat');
    });
    Route::apiResource('threads.calls.participants', CallParticipantController::class)->scoped()->except(['store', 'destroy']);
});
