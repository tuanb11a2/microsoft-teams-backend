<?php

namespace Modules\UserApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VideoGrant;
use Illuminate\Support\Str;
use Twilio\Rest\Client;

class TwilioController extends Controller
{
    public function checkRoomExists(Request $request): JsonResponse
    {
        $accountSid = config('twilio.account_sid');
        $authToken = config('twilio.auth_token');
        $client = new Client($accountSid, $authToken);

        $roomExists = true;
        try {
            $client->video->v1->rooms($request->get('roomName'))->fetch();
        } catch (\Exception $e) {
            $roomExists = false;
        };

        return response()->json(["roomExists" => $roomExists, 'roomName' => $request->get('roomName')]);
    }

    public function getAccessToken(Request $request)
    {
        $accountSid = config('twilio.account_sid');
        $apiKeySid = config('twilio.api_key_sid');
        $apiKeySecret = config('twilio.api_key_secret');

        $identity = Str::uuid();

        // Create an Access Token
        $token = new AccessToken(
            $accountSid,
            $apiKeySid,
            $apiKeySecret,
            3600,
            $identity
        );

        // Grant access to Video
        $grant = new VideoGrant();
        $grant->setRoom($request->get('roomName'));
        $token->addGrant($grant);

        // Serialize the token as a JWT
        return response()->json($token->toJWT());
    }
}
