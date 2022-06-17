<?php

namespace Modules\UserApi\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\Group;
use App\Models\Channel;
use App\Transformers\SuccessResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\UserApi\Transformers\GroupResource;
use Illuminate\Support\Str;
use Laravolt\Avatar\Facade as Avatar;
use Modules\UserApi\Http\Requests\StoreChannelRequest;
use Modules\UserApi\Http\Requests\StoreGroupRequest;
use Modules\UserApi\Http\Requests\UpdateChannelRequest;
use Modules\UserApi\Transformers\ChannelResource;
use Modules\UserApi\Transformers\UsersResource;

class GroupController extends Controller
{
    public const PER_PAGE = 10;

    public function index()
    {
        $groups = Group::query()->whereHas('users', function (Builder $query) {
            $query->where('user_id', auth()->user()->id);
        })->withCount('users')->get();

        return GroupResource::make($groups);
    }

    public function getOtherGroups()
    {
        $groups = Group::query()->whereDoesntHave('users', function (Builder $query) {
            $query->where('user_id', auth()->user()->id);
        })->withCount('users')->get();

        return GroupResource::make($groups);
    }

    public function search(Request $request)
    {
    }

    public function show(string $group): GroupResource
    {
        $group = Group::query()->where('slug', $group)->with(['channels', 'users'])->first();

        if (!$group) {
            throw ApiException::notFound('Nhóm không tồn tại!');
        }

        return GroupResource::make($group);
    }

    public function getUsers(string $group): UsersResource
    {
        $group = Group::query()->where('slug', $group)->first();

        if (!$group) {
            throw ApiException::notFound('Nhóm không tồn tại!');
        }

        $users = $group->users()->with('posts')->paginate(self::PER_PAGE);

        return UsersResource::make($users);
    }

    /**
     *
     * @param StoreGroupRequest $request
     * @return GroupResource
     */
    public function store(StoreGroupRequest $request): GroupResource
    {
        $imageName = $request->get('slug') . '.png';

        if (!$request->has('avatar')) {
            $avatar = Avatar::create($request->get('name'))->getImageObject()->save($imageName);
            $path = 'images/groups/' . $imageName;
            Storage::put($path, $avatar);
            $s3Path = Storage::url($path);
        } else {
            $avatar = $request->file('avatar');
            $path = Storage::put('images/groups', $avatar);
            $s3Path = Storage::url($path);
        }

        $group = Group::query()->create([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'slug' => Str::uuid()->toString(),
            'creator_id' => Auth::user()->id,
            'privacy' => $request->get('privacy'),
            'avatar' => $s3Path
        ]);
        $group->channels()->create([
            'name' => 'Chung',
            'slug' => 'general',
        ]);
        $group->users()->attach([
            'user_id' => $request->get('user_id'),
        ]);


        return GroupResource::make($group);
    }

    public function update(Request $request)
    {
    }

    /**
     * Delete Group
     *
     * @param integer $id
     * @return void
     */
    public function destroy(int $id)
    {
        $group = Group::query()->find($id);
        if (!$group) {
            throw ApiException::notFound('Nhóm không tồn tại!');
        }

        if (Auth::id() !== $group->creator_id) {
            throw ApiException::unauthorized('Bạn không có quyền xóa nhóm này!');
        }
        $channels = $group->channels;
        foreach ($channels as $channel) {
            foreach ($channel->posts as $post) {
                $post->comments()->delete();
                $post->delete();
            }
            foreach ($channel->exercises as $exercise) {
                $exercise->comments()->delete();
                $exercise->users()->detach();
                $exercise->delete();
            }
            $channel->delete();
        }
        $group->users()->detach();
        $group->todos()->delete();
        $group->delete();

        return new SuccessResource();
    }

    public function addMembers(int $groupId, Request $request)
    {
        $group = Group::query()->find($groupId);
        if (!$group) {
            throw ApiException::notFound('Nhóm không tồn tại!');
        }

        $members = collect($request->get('users'))->pluck('id');
        $group->users()->attach($members);

        return new SuccessResource();
    }

    public function getChannel(string $groupSlug, string $channelSlug): GroupResource
    {
        $group = Group::query()->where('slug', $groupSlug)->with(['channels', 'users'])->first();

        $channel = Channel::query()->whereHas('group', function (Builder $query) use ($group) {
            $query->where('group_id', $group->id);
        })->where('slug', $channelSlug)->with('exercises.submissions')->with('exercises.users')->with('posts', function ($query) {
            $query->with(['comments.user', 'user'])->orderByDesc('updated_at');
        })->first();

        return GroupResource::make(['group' => $group, 'channel' => $channel]);
    }

    /**
     * Add Channel
     *
     * @param integer $groupId
     * @param StoreChannelRequest $request
     * @return ChannelResource
     */
    public function addChannel(int $groupId, StoreChannelRequest $request): ChannelResource
    {
        $group = Group::find($groupId);
        if (!$group) {
            throw ApiException::notFound('Nhóm không tồn tại!');
        }
        $name = $request->get('name');
        $channel = $group->channels()->create([
            'name' => $name,
            'slug' => Str::slug($name)
        ]);

        return ChannelResource::make($channel);
    }

    /**
     *  Update Channel
     *
     * @param integer $groupId
     * @param UpdateChannelRequest $request
     * @return ChannelResource
     */
    public function updateChannel(int $groupId, int $channelId, UpdateChannelRequest $request): ChannelResource
    {
        $group = Group::find($groupId);
        if (!$group) {
            throw ApiException::notFound('Nhóm không tồn tại!');
        }
        $channel = $group->channels()->find($channelId);
        if (!$channel) {
            throw ApiException::notFound('Kênh không tồn tại!');
        }
        $channel->update([
            'name' => $request->get('name'),
            'slug' => Str::slug($request->get('name'))
        ]);

        return ChannelResource::make($channel);
    }


    /**
     * Delete Channel
     *
     * @param integer $groupId
     * @param integer $channelId
     * @return SuccessResource
     */
    public function deleteChannel(int $groupId, int $channelId): SuccessResource
    {
        $group = Group::find($groupId);
        if (!$group) {
            throw ApiException::notFound('Nhóm không tồn tại!');
        }

        if (Auth::id() !== $group->creator_id) {
            throw ApiException::unauthorized('Bạn không có quyền xóa kênh này!');
        }

        $channel = $group->channels()->find($channelId);

        if (!$channel) {
            throw ApiException::notFound('Kênh không tồn tại!');
        }

        foreach ($channel->posts as $post) {
            $post->comments()->delete();
            $post->delete();
        }
        foreach ($channel->exercises as $exercise) {
            $exercise->comments()->delete();
            $exercise->users()->detach();
            $exercise->delete();
        }
        $channel->delete();

        return new SuccessResource();
    }

    /**
     * Remove member from group
     *
     * @param integer $groupId
     * @param integer $memberId
     * @return SuccessResource
     */
    public function removeMember(int $groupId, int $memberId): SuccessResource
    {
        $group = Group::query()->find($groupId);

        if (!$group) {
            throw ApiException::notFound('Nhóm không tồn tại!');
        }

        if (!$group->users()->where('user_id', $memberId)->exists()) {
            throw ApiException::forbidden('Bạn không là thành viên cuả nhóm này!');
        }

        $userId = Auth::user()->id;

        if ($group->creator_id != $userId && $memberId != $userId) {
            throw ApiException::forbidden('Bạn không có quyền xóa thành viên khác!');
        }

        $group->users()->detach($memberId);

        return new SuccessResource();
    }

    public function leaveGroup(int $groupId): SuccessResource
    {
        $userId = Auth::user()->id;
        $group = Group::query()->find($groupId);

        if (!$group) {
            throw ApiException::notFound('Nhóm không tồn tại!');
        }
        if ($group->creator_id == $userId) {
            throw ApiException::forbidden('Bạn không thể rời khỏi nhóm!');
        }

        if (!$group->users()->where('user_id', $userId)->exists()) {
            throw ApiException::forbidden('Bạn không là thành viên cuả nhóm này!');
        }

        $group->users()->detach($userId);

        return new SuccessResource();
    }

    /**
     * Join group
     *
     * @param integer $groupId
     * @param string $joinCode
     * @return SuccessResource
     */
    public function joinGroup(int $groupId, string $joinCode): SuccessResource
    {
        $userId = Auth::user()->id;
        $group = Group::query()->find($groupId);

        if (!$group) {
            throw ApiException::notFound('Nhóm không tồn tại!');
        }

        if ($group->users()->where('user_id', $userId)->exists()) {
            throw ApiException::forbidden('Bạn đã là thành viên cuả nhóm này!');
        }

        if ($group->privacy === 'private') {
            if ($group->join_code !== $joinCode) {
                throw ApiException::forbidden('Mã tham gia không chính xác!');
            }
        }

        $group->users()->attach($userId);

        return new SuccessResource();
    }
}
