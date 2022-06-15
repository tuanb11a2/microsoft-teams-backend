<?php

namespace Modules\UserApi\Repositories;

use App\Models\User;
use Modules\UserApi\Contracts\AuthRepoInterface;
use Modules\UserApi\DTOs\RegisterParams;

class AuthRepo implements  AuthRepoInterface
{
    public function checkExists(string $identity): User
    {
        return User::where('email', $identity)
            ->orWhere('username', $identity)
            ->orWhere('phone_number', $identity)
            ->firstOrFail();
    }

    public function checkAccount(string $identity): bool
    {
        return User::query()->where('email', $identity)->exists();
    }

    public function register(RegisterParams $registerParams)
    {
        return User::query()->create([
            'name' => $registerParams->name,
            'username' => $registerParams->username,
            'email' => $registerParams->email,
            'password' => $registerParams->password,
            'phone_number' => $registerParams->phone_number,
            $registerParams->provider.'_id' => $registerParams->social_id,
            'avatar' => $registerParams->avatar,
        ]);
    }
}
