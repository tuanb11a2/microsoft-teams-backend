<?php

namespace Modules\UserApi\Http\Controllers;

use App\Events\PostComment;
use App\Exceptions\ApiException;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Post;
use App\Transformers\SuccessResource;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\UserApi\Transformers\ChannelResource;

class ChannelController extends Controller
{
    public function newComment(Request $request, int $channelId, int $postId)
    {
        $userId = Auth::user()->id;
        $channel = Channel::find($channelId);
        if (!$channel) {
            throw ApiException::notFound('Kênh không tồn tại!');
        }

        $group = $channel->group;

        if (!$group->users->contains($userId)) {
            throw ApiException::forbidden();
        }

        $comment = Comment::query()->create([
            'content' => $request->get('content'),
            'user_id' => $userId,
            'post_id' => $postId,
        ])->load('user');

        broadcast(new PostComment($comment, $group->id, $channelId));

        return ChannelResource::make($comment);
    }

    public function newExercise(Request $request, int $groupId, int $channelId)
    {
    }

    public function submitExercise(Request $request, int $groupId, int $channelId)
    {
    }

    public function deleteExercise(int $groupId, int $channelId, int $exerciseId)
    {
    }

    /**
     * Add Post
     *
     * @param Request $request
     * @param integer $groupId
     * @param integer $channelId
     * @return ChannelResource
     */
    public function newPost(Request $request, int $groupId, int $channelId): ChannelResource
    {
        $channel = $this->checkGroupAndChannel($groupId, $channelId);

        if ($request->has('file')) {
            $file = $request->file('file');
            $path = Storage::put('images/posts', $file);
            $s3FilePath = Storage::url($path);
        }
        $post = $channel->posts()->create([
            'content' => $request->get('content'),
            'user_id' => Auth::user()->id,
            'file_path' => $s3FilePath ?? null,
        ])->load(['user', 'comments']);

        return ChannelResource::make($post);
    }

    /**
     * Add Post
     *
     * @param Request $request
     * @param integer $groupId
     * @param integer $channelId
     * @return ChannelResource
     */
    public function newCall(Request $request, int $groupId, int $channelId): ChannelResource
    {
        $channel = $this->checkGroupAndChannel($groupId, $channelId);

        $post = $channel->posts()->create([
            'content' => $request->get('content'),
            'user_id' => Auth::user()->id,
        ]);

        return ChannelResource::make($post);
    }

    /**
     * update post
     *
     * @param Request $request
     * @param integer $groupId
     * @param integer $channelId
     * @param integer $postId
     * @return ChannelResource
     */
    public function updatePost(Request $request, int $groupId, int $channelId, int $postId): ChannelResource
    {
        $channel = $this->checkGroupAndChannel($groupId, $channelId);
        $post = Post::find($postId);
        if (!$post) {
            throw ApiException::notFound('Bài viết không tồn tại!');
        }

        if ($request->has('file')) {
            $file = $request->get('file');
            $filePath = 'posts/' . $file->getClientOriginalName();
            Storage::put($filePath, $file);
            $s3FilePath = Storage::url($filePath);
        }

        $post->update([
            'content' => $request->get('content'),
            'user_id' => Auth::user()->id,
            'file_path' => $s3FilePath
        ]);

        return ChannelResource::make($post);
    }

    /**
     * delete post
     *
     * @param integer $groupId
     * @param integer $channelId
     * @param integer $postId
     * @return SuccessResource
     */
    public function deletePost(int $groupId, int $channelId, int $postId): SuccessResource
    {
        $this->checkGroupAndChannel($groupId, $channelId);
        $post = Post::find($postId);
        if (!$post) {
            throw ApiException::notFound('Bài viết không tồn tại!');
        }
        $post->delete();

        return new SuccessResource();
    }

    /**
     * Check group and channel
     *
     * @param integer $groupId
     * @param integer $channelId
     * @return Channel
     */
    private function checkGroupAndChannel(int $groupId, int $channelId): Channel
    {
        $group = Group::find($groupId);

        if (!$group) {
            throw ApiException::notFound('Nhóm không tồn tại!');
        }

        $channel = $group->channels()->find($channelId);

        if (!$channel) {
            throw ApiException::notFound('Kênh không tồn tại!');
        }

        return $channel;
    }
}
