<?php

namespace Modules\UserApi\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UserApi\Http\Requests\LoginRequest;
use Modules\UserApi\Http\Requests\RegisterRequest;

interface AuthServiceInterface
{
    public function login (LoginRequest $request);

    public function register (RegisterRequest $request);

    public function socialLogin(Request $request);

    public function socialLoginCallback(Request $request);

    public function refresh();

    public function respondWithToken(string $token);

    public function checkExists(string $identity): object;

    public function checkAccount(string $identity): bool;
}
