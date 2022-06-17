<?php

namespace Modules\UserApi\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\Friend;
use App\Models\Message;
use App\Models\User;
use App\Transformers\SuccessResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\UserApi\Transformers\UserResource;

class FriendController extends Controller
{
    /**
     *
     * @return UserResource
     */
    public function getAllFriends(): UserResource
    {
        $userId = Auth::id();
        $friends = User::query()->whereHas('userFriends', function (Builder $query) use ($userId) {
            $query->where('friend_id', $userId)->where('status', 'accepted');
        })->orWhereHas('friendsUser', function (Builder $query) use ($userId) {
            $query->where('user_id', $userId)->where('status', 'accepted');
        })->get();

        return UserResource::make($friends);
    }

    /**
     * Get pending Friends
     *
     * @return UserResource
     */
    public function getpendingFriends(): UserResource
    {
        $userId = Auth::id();
        $friends = User::query()->whereHas('userFriends', function (Builder $query) use ($userId) {
            $query->where('friend_id', $userId)->where('status', 'pending');
        })->get();

        return UserResource::make($friends);
    }

    /**
     * Get friends with messages
     *
     * @param integer $userId
     * @return UserResource
     */
    public function getFriendsWithMessages(): UserResource
    {
        $userId = Auth::id();
        $friends = User::query()->whereHas('userFriends', function (Builder $query) use ($userId) {
            $query->where('friend_id', $userId)->where('status', 'accepted');
        })->orWhereHas('friendsUser', function (Builder $query) use ($userId) {
            $query->where('user_id', $userId)->where('status', 'accepted');
        })->get();
        foreach ($friends as $friend) {
            $friend->messages = Message::query()->where([['sender_id', $friend->id], ['receiver_id', $userId]])
                ->orWhere([['sender_id', $userId], ['receiver_id', $friend->id]])->orderByDesc('updated_at')->get();
            $friend->lastest_message = $friend->messages->first()->created_at ?? null;
        };
        $sortedFriends = $friends->sortByDesc(function ($friend) {
            return $friend->lastest_message;
        })->values()->all();

        return UserResource::make($sortedFriends);
    }

    /**
     * Find friends
     *
     * @param Request $request
     * @return UserResource
     */
    public function findFriends(Request $request): UserResource
    {
        $user = Auth::user();
        $friends = $user->whereHas('userFriends', function (Builder $query) {
            $query->where('status', 'accepted');
        })->where('name', 'like', '%' . $request->get('keyword') . '%')->get();

        return UserResource::make($friends);
    }

    /**
     * Add friend
     *
     * @param integer $friendId
     * @return SuccessResource
     */
    public function addFriend(int $friendId): SuccessResource
    {
        $user = Auth::user();
        $user->userFriends()->create([
            'friend_id' => $friendId,
            'status' => 'pending',
        ]);

        return new SuccessResource();
    }

    /**
     * Decline friend
     *
     * @param integer $friendId
     * @return SuccessResource
     */
    public function declineFriend(int $friendId): SuccessResource
    {
        $user = Auth::user();
        $friend = $user->friendsUser()->where('user_id', $friendId)->where('status', 'pending');
        if (!$friend->count()) {
            throw ApiException::notFound('Người dùng này không có quan hệ bạn bè với bạn!');
        }

        $friend->update([
            'status' => 'declined'
        ]);

        return new SuccessResource();
    }

    /**
     * Accept friend
     *
     * @param integer $friendId
     * @return SuccessResource
     */
    public function acceptFriend(int $friendId): SuccessResource
    {
        $user = Auth::user();
        $friend = Friend::where([['friend_id', $user->id], ['user_id', $friendId], ['status', 'pending']])->first();
        if (!$friend) {
            throw ApiException::notFound('Người dùng này không có lời mời kết bạn với bạn!');
        }

        $friend->status = 'accepted';
        $friend->save();
        Friend::query()->create([
            'user_id' => $user->id,
            'friend_id' => $friendId,
            'status' => 'accepted',
        ]);

        return new SuccessResource();
    }

    /**
     * Remove Friend
     *
     * @param integer $friendId
     * @return SuccessResource
     */
    public function removeFriend(int $friendId): SuccessResource
    {
        $user = Auth::user();
        $friend = Friend::where([['friend_id', $friendId], ['user_id', $user->id]])->orWhere([['friend_id', $user->id], ['user_id', $friendId]])->first();

        if (!$friend) {
            throw ApiException::notFound('Bạn không có quan hệ bạn bè với người dùng này!');
        }

        $friend->delete();

        return new SuccessResource();
    }

    /**
     *
     * @return UserResource
     */
    public function getSuggestFriends(): UserResource
    {
        $userId = Auth::user()->id;
        $friends = User::query()->whereHas('userFriends', function (Builder $query) use ($userId) {
            $query->where('friend_id', $userId)->whereIn('status', ['accepted', 'pending']);
        })->orWhereHas('friendsUser', function (Builder $query) use ($userId) {
            $query->where('user_id', $userId)->whereIn('status', ['accepted', 'pending']);
        })->get()->except($userId);

        $users = User::all()->except($userId);

        $suggestFriends = $users->diff($friends);

        return UserResource::make($suggestFriends);
    }
}
