<?php

use Modules\UserApi\Http\Controllers\TwilioController;
use Illuminate\Support\Facades\Route;
use Modules\UserApi\Http\Controllers\UserController;
use Modules\UserApi\Http\Controllers\AuthController;
use Modules\UserApi\Http\Controllers\ChannelController;
use Modules\UserApi\Http\Controllers\FriendController;
use Modules\UserApi\Http\Controllers\GroupController;
use Modules\UserApi\Http\Controllers\MessageController;
use Modules\UserApi\Http\Controllers\ExerciseController;
use Modules\UserApi\Http\Controllers\TodoController;

Route::middleware('lang')->group(function () {
    Route::group([
        'prefix' => 'auth'
    ], function () {
        Route::post('social/login/{provider}', [AuthController::class, 'socialLogin']);
        Route::get('social/login/{provider}/callback', [AuthController::class, 'socialLoginCallback']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth.jwt');
        Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth.jwt');
        Route::get('user', [AuthController::class, 'me'])->middleware('auth.jwt');
    });
    Route::middleware('auth.jwt')->group(function () {
        Route::get('/feed', [UserController::class, 'feed']);
        Route::prefix('friends')->as('friends.')->group(function () {
            Route::get('/all', [FriendController::class, 'getAllFriends']);
            Route::get('/suggest', [FriendController::class, 'getSuggestFriends']);
            Route::get('/pending', [FriendController::class, 'getPendingFriends']);
            Route::get('/with-messages', [FriendController::class, 'getFriendsWithMessages']);
            Route::post('/search', [FriendController::class, 'findFriends']);
            Route::get('/{friendId}/add', [FriendController::class, 'addFriend']);
            Route::get('/{friendId}/accept', [FriendController::class, 'acceptFriend']);
            Route::get('/{friendId}/decline', [FriendController::class, 'declineFriend']);
            Route::get('/{friendId}/remove', [FriendController::class, 'removeFriend']);
        });
        Route::prefix('messages')->as('messages.')->group(function () {
            Route::get('/{receiverId}', [MessageController::class, 'getMessages'])->name('get');
            Route::post('/send', [MessageController::class, 'sendMessage'])->name('send');
        });

        Route::prefix('users')->as('users.')->group(function () {   
            Route::get('/{id}', [UserController::class, 'getUser']);
        });

        Route::post('twilio/access_token', [TwilioController::class, 'getAccessToken']);
        Route::post('twilio/check-exists', [TwilioController::class, 'checkRoomExists']);
        
        Route::get('groups/others', [GroupController::class, 'getOtherGroups']);
        Route::resource('groups', GroupController::class)->except(['create', 'edit']);
        Route::get('groups/{group}/users', [GroupController::class, 'getUsers']);
        Route::get('/groups/{groupId}/leave', [GroupController::class, 'leaveGroup']);
        Route::get('/groups/{groupId}/join/{userId}', [GroupController::class, 'joinGroup']);

        Route::delete('/groups/{groupId}/remove-member/{memberId}', [GroupController::class, 'removeMember']);
        Route::post('/groups/{groupId}/add-members', [GroupController::class, 'addMembers']);

        Route::get('/groups/{groupSlug}/channels/{channelSlug}', [GroupController::class, 'getChannel']);
        Route::post('/groups/{groupId}/channels', [GroupController::class, 'addChannel']);
        Route::put('/groups/{groupId}/channels/{channelId}', [GroupController::class, 'updateChannel']);
        Route::delete('/groups/{groupId}/channels/{channelId}', [GroupController::class, 'deleteChannel']);

        Route::post( '/channels/{channelId}/posts/{postId}/comments', [ChannelController::class, 'newComment']);

        Route::post('/groups/{groupId}/channels/{channelId}/posts', [ChannelController::class, 'newPost']);
        Route::post('/groups/{groupId}/channels/{channelId}/calls', [ChannelController::class, 'newCall']);
        Route::delete('/groups/{groupId}/channels/{channelId/posts/{postId}', [ChannelController::class, 'deletePost']);
        Route::put('/groups/{groupId}/channels/{channelId}/posts/{postId}', [ChannelController::class, 'updatePost']);
        
        Route::resource('todo', TodoController::class)->except(['create', 'edit']);

        Route::post('/exercises/{exerciseId}/submit', [ExerciseController::class, 'submitExercise']);
        Route::resource('exercises', ExerciseController::class)->except(['create', 'edit']);
        Route::get('exercises/groups/{groupId}', [ExerciseController::class, 'filterByGroup']);
        Route::post('/exercises/{id}/submissions/{submissionId}/mark', [ExerciseController::class, 'mark']);
    });
});
