<?php

namespace Modules\UserApi\Contracts;

use App\Models\User;
use Modules\UserApi\DTOs\RegisterParams;


interface AuthRepoInterface
{
    public function checkExists(string $identity): User;

    public function checkAccount(string $identity): bool;

    public function register(RegisterParams $registerParams);
}
