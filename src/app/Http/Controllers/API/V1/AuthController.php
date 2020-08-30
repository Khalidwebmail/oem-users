<?php

namespace App\Http\Controllers\API\V1;

use App\User;
use Validator;
use Carbon\Carbon;
use GuzzleHttp\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends BaseController
{

    public function __construct()
    {
        $this->middleware('auth:api')->except(['signup', 'login']);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'verify_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::create($request->only(['email', 'password']));

        if ($user) {
            $user->assignRole($request->role);

            Redis::publish(env('CHANNEL_PREFIX').'registration', $user);
        }

        return $this->sendResponse([], 'Registration request accepted. Please check you inbox', Response::HTTP_OK);
    }

    public function completeRegistration($email, $token)
    {
        //FIND USER DETAILS
        $user = User::where('email', $email)
            ->where('email_verification_token', $token)
            ->where('is_email_verified', '0')
            ->first();

        //REDIRECT TO HOME IF NO USER FOUND FOR PAYLOAD DATA
        if (!$user) {
            return $this->sendError('User not found', 'Bad Request', 400);
        }

        //ACTIVATE USER
        $user->is_email_verified = true;
        $user->email_verified_at = Carbon::now();
        $user->status = User::STATUS_ACTIVE;
        $user->save();

        Redis::publish(env('CHANNEL_PREFIX').'registration.complete', $user);

        return $this->sendResponse([], "Registration successful.");
    }

    /**
     * login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data['token'] = $this->createNewToken($token);
        $data['user'] = auth()->user();
        return $this->sendResponse($data, 'User login successfully.', Response::HTTP_OK);

    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return $this->sendResponse([], 'User successfully signed out.', Response::HTTP_OK);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $data['token'] = $this->createNewToken(auth()->refresh());
        $data['user'] = auth()->user();
        return $this->sendResponse( $data, 'User token.', Response::HTTP_OK);

    }
}
