<?php

namespace Modules\UserApi\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\Group;
use App\Models\Exercise;
use App\Models\Channel;
use App\Transformers\SuccessResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\UserApi\Transformers\ExerciseResource;
use Illuminate\Support\Str;
use Modules\UserApi\Http\Requests\StoreExerciseRequest;

class ExerciseController extends Controller
{
    public function index()
    {
        $exercises = Exercise::query()->whereHas('users', function (Builder $query) {
            $query->where('user_id', auth()->user()->id);
        })->with(['channel.group', 'comments', 'submissions'])->withCount('users')->orderByDesc('updated_at')->get();

        return ExerciseResource::make($exercises);
    }

    /**
     * Mark grade
     *
     * @param Request $request
     * @param integer $id
     * @param integer $submissionId
     * @return SuccessResource
     */
    public function mark(Request $request, int $id, int $submissionId): SuccessResource
    {
        $exercise = Exercise::query()->find($id);
        if (!$exercise) {
            throw ApiException::notFound('Bài tập không tồn tại!');
        }

        $submission = $exercise->submissions->find($submissionId);
        if (!$submission) {
            throw ApiException::notFound('Lần nộp bài không tồn tại!');
        }

        $submission->update([
            'grade' => $request->grade
        ]);

        return new SuccessResource();

    }

    public function filterByGroup(int $groupId)
    {
        $exercises = Exercise::query()->whereHas('users', function (Builder $query) {
            $query->where('user_id', auth()->user()->id);
        })->whereHas('channel', function ($query) use ($groupId) {
            $query->where('group_id', $groupId);
        })->with('channel.group')->orderByDesc('updated_at')->get();

        return ExerciseResource::make($exercises);
    }

    public function show(int $id)
    {
        $exercise = Exercise::query()->with(['channel.group', 'comments', 'submissions.user'])->withCount('users')->find($id);

        return ExerciseResource::make($exercise);
    }

    public function store(StoreExerciseRequest $request): ExerciseResource
    {
        $channel = Channel::query()->find($request->channel_id);

        if (!$channel) {
            throw ApiException::notFound('Kênh không tồn tại!');
        }
        $group = $channel->group;
        $channelMembersId = $group->users->pluck('id')->toArray();

        if ($request->has('file')) {
            $file = $request->file('file');
            $path = Storage::put('files/exercises', $file);
            $s3FilePath = Storage::url($path);
        }

        $exercise = $channel->exercises()->create([
            'title' => $request->get('title'),
            'description' => $request->get('description') ?? '',
            'deadline' => $request->get('deadline'),
            'file_path' => $s3FilePath ?? '',
        ]);
        $exercise->users()->attach($channelMembersId);
        $exercise->load('channel');

        return ExerciseResource::make($exercise);
    }

    /**
     *
     * @param integer $exerciseId
     * @param Request $request
     * @return ExerciseResource
     */
    public function submitExercise(int $exerciseId, Request $request): ExerciseResource
    {
        $userId = Auth::id();
        $exercise = Exercise::query()->find($exerciseId);
        if (!$exercise) {
            throw ApiException::notFound('Bài tập không tồn tại!');
        }

        $submission = $exercise->submissions()->where('user_id', $userId)->first();

        $s3FilePath = $submission->file_path ?? '';
        
        if ($request->has('file')) {
            $file = $request->file('file');
            $path = Storage::put('files/exercises/submissions', $file);
            $s3FilePath = Storage::url($path);
        }


        if (!$submission) {
            $submission = $exercise->submissions()->create([
                'user_id' => $userId,
                'content' => $request->get('content'),
                'file_path' => $s3FilePath
            ]);

            return ExerciseResource::make($submission);
        }

        $submission->update([
            'content' => $request->get('content'),
            'file_path' => $s3FilePath
        ]);

        $submission->save();

        return ExerciseResource::make($submission);
    }
}
