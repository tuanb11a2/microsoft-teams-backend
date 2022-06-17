<?php

namespace Modules\UserApi\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\UserApi\Transformers\UserResource;

class UserController extends Controller
{
    public function getUser(int $id): UserResource
    {
        $user = User::query()->find($id);
        if (!$user) {
            throw ApiException::notFound();
        }

        return UserResource::make($user);
    }

    /**
     *  Get posts on index page
     * 
     * @return UserResource
     */
    public function feed(): UserResource
    {
        $userId = Auth::id();
        $groups = Group::query()->whereHas('users', function (Builder $query) use ($userId) {
            $query->where('user_id', $userId);
        })->with(['channels.posts.user', 'channels.posts.channel.group', 'channels.posts.comments.user'])->get();

        $posts = $groups->pluck('channels')->flatten()->pluck('posts')->flatten();


        return UserResource::make($posts);
    }
}
