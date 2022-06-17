<?php

namespace Modules\UserApi\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\Group;
use App\Models\Todo;
use App\Transformers\SuccessResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\UserApi\Transformers\TodoResource;
use Illuminate\Support\Str;
use Modules\UserApi\Http\Requests\StoreTodoRequest;

class TodoController extends Controller
{
    public function index()
    {
        $todoList = Todo::query()->where('user_id', auth()->user()->id)->with(['group', 'user'])->orderBy('deadline')->get();

        return TodoResource::make($todoList);
    }

    public function store(StoreTodoRequest $request)
    {
        $todo = Todo::query()->create([
            'name' => $request->get('name'),
            'deadline' => $request->get('deadline'),
            'user_id' => auth()->user()->id,
            'group_id' => $request->get('groupId'),
            'priority' => $request->get('priority'),
            'type' => $request->get('type'),
        ])->load('group');

        return TodoResource::make($todo);
    }

    public function update(int $todoId, Request $request)
    {
        $todo = Todo::query()->find($todoId);
        $todo->update([
            'name' => $request->get('name'),
            'deadline' => $request->get('deadline'),
            'priority' => $request->get('priority'),
            'group_id' => $request->get('groupId'),
            'type' => $request->get('type'),
        ]);

        return new SuccessResource();
    }

    public function destroy(int $id)
    {
        $todo = Todo::find($id);

        if (!$todo) {
            throw ApiException::notFound('Việc cần làm không tồn tại!');
        }

        $todo->delete();

        return new SuccessResource();
    }
}
