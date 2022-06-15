<?php

namespace Modules\UserApi\DTOs;

class RegisterParams
{
    public string $name;

    public string $username;

    public string $email;

    public string $password;

    public string $phone_number;

    public string $provider;

    public string $social_id;

    public string $avatar;

    public function __construct (string $name, string $username, string $email, string $password, string $phone_number, ?string $provider, ?string $social_id, string $avatar)
    {
        $this->name = $name;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->phone_number = $phone_number;
        $this->provider = $provider;
        $this->social_id = $social_id;
        $this->avatar = $avatar;
    }
}
