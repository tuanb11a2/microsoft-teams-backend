<?php

namespace Modules\UserApi\Services;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravolt\Avatar\Facade as Avatar;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Modules\UserApi\Contracts\AuthServiceInterface;
use Modules\UserApi\DTOs\RegisterParams;
use Modules\UserApi\Http\Requests\LoginRequest;
use Modules\UserApi\Http\Requests\RegisterRequest;
use Modules\UserApi\Repositories\AuthRepo;

class AuthService implements AuthServiceInterface
{
    protected AuthRepo $authRepo;

    public function __construct(AuthRepo $authRepo)
    {
        $this->authRepo = $authRepo;
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->checkExists($request->get('identity'));

        if (!password_verify($request->get('password'), $user->password)) {
            throw ApiException::unauthorized(trans('auth.password'));
        }

        return $this->respondWithToken(auth()->tokenById($user->id));
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $imageName = $request->get('username') . '.png';
        if (!$request->has('avatar')) {
            $avatar = Avatar::create($request->get('name'))->getImageObject()->save($imageName);
            $path = 'images/avatars/' . $imageName;
            Storage::put($path, $avatar);
            $s3FilePath = Storage::url($path);
        } else {
            $avatar = $request->file('avatar');
            $path = Storage::put('images/avatars/' . $avatar->getClientOriginalName(), $avatar);
            $s3FilePath = Storage::url($path);
        }

        $registerParams = new RegisterParams(
            $request->get('name'),
            $request->get('username'),
            $request->get('email'),
            bcrypt($request->get('password')),
            $request->get('phone_number'),
            $request->get('provider') ? $request->get('provider') : '',
            $request->get('social_id') ? $request->get('social_id') : '',
            $s3FilePath
        );

        $user = $this->authRepo->register($registerParams);

        return $this->respondWithToken(auth()->tokenById($user->id));
    }

    public function socialLogin(Request $request): string
    {
        $provider = $request->route('provider');

        return Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
    }

    public function socialLoginCallback(Request $request): RedirectResponse
    {
        $provider = $request->route('provider');
        $socialUser = Socialite::driver($provider)->stateless()->user();
        $socialUser->user['provider'] = $provider;
        $socialUser['username'] = substr($socialUser->email, 0, strpos($socialUser->email, '@'));
        $dbUser = User::query()->where('email', $socialUser->email)->first();
        $providerId = $provider . '_id';

        if ($dbUser) {
            $dbUser[$providerId] = $socialUser->id;
            $dbUser->save();
            
            return Redirect::to(config('app.client_url') . '/login/callback/' . $provider . '?token=' . auth()->tokenById($dbUser->id));
        }
        return Redirect::to(config('app.client_url') . '/login/callback/' . $provider . '/new/?user=' . json_encode($socialUser->user));
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    public function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => 1
        ]);
    }


    public function checkExists(string $identity): User
    {
        try {
            return $this->authRepo->checkExists($identity);
        } catch (ModelNotFoundException $e) {
            throw ApiException::notFound(trans('auth.failed'));
        }
    }

    public function checkAccount(string $identity): bool
    {
        if (!$this->authRepo->checkAccount($identity)) {
            throw ApiException::notFound(trans('auth.failed'));
        }

        return $this->authRepo->checkAccount($identity);
    }
}
