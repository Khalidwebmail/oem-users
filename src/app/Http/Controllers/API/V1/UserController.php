<?php

namespace App\Http\Controllers\API\V1;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use Exception;
use Facade\FlareClient\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserController extends BaseController
{

    public function __construct()
    {
        // $this->middleware('auth:api')->except(['signup', 'login']);
    }

    public function index(Request $request)
    {
        $paginate = $request->has('paginate') && intval($request->paginate) > 0 ? intval($request->paginate) : 10;

        $users = User::paginate($paginate);

        return JsonResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {

        // Retrieve the validated input data...
        $validated = $request->validated();

        $data = $request->all();

        if ($request->password) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = User::create($data);

        Redis::publish(env('CHANNEL_PREFIX').'store', $user);

        return new JsonResource($user);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show($user)
    {
        $user = User::findOrfail($user);

        return new JsonResource($user);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\UserRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, User $user)
    {
        $request->validated();

        $data = $request->all();

        if ($request->password) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        $user->refresh();

        Redis::publish(env('CHANNEL_PREFIX').'update', $user);

        return new JsonResource($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy($user)
    {
        $user = User::find($user);
        if(! $user) {
            return response()->json(['error' => 'This user does not exists'], 404);
        }
        $user->delete();

        return response()->json(['message' => 'User Deletion Successful!'], 200);
    }


    /**
     * Active specified resource.
     *
     * @param  \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function active(User $user)
    {
        $user->status = User::STATUS_ACTIVE;
        $user->save();

        Redis::publish(env('CHANNEL_PREFIX').'active', $user);

        return new JsonResource($user);
    }

    /**
     * Suspend specified resource.
     *
     * @param  \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function suspend(User $user)
    {
        $user->status = User::STATUS_SUSPENDED;
        $user->save();

        Redis::publish(env('CHANNEL_PREFIX').'suspend', $user);

        return new JsonResource($user);
    }

    /**
     * @param $email
     * @param $token
     * @return \Illuminate\Http\Response
     */
    public function verify(Request $request, $email, $token)
    {
        //FIND USER DETAILS
        $user = User::where('email', $email)
            ->where('email_verification_token', $token)
            ->where('is_email_verified', '0')
            ->firstOrFail();


        $validator = Validator::make(
            $request->only(['password', 'verify_password']),
            [
                'password' => 'required|min:6',
                'verify_password' => 'required|same:password',
            ]
        );

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json($validator->errors(), 422));
        }

        $user->password = bcrypt(request()->password);



        //ACTIVATE USER
        $user->is_email_verified = true;
        $user->email_verified_at = Carbon::now();
        $user->status = User::STATUS_ACTIVE;
        $user->save();

        Redis::publish(env('CHANNEL_PREFIX').'email.verified', $user);

        return response()->json(["success" => true], 200);
    }

    public function varifyOTP(Request $request, $user)
    {
        $user = User::findOrFail($user);

        if (
            request()->has('otp')
            &&
            strlen(intval(request()->otp)) === 6
            &&
            request()->otp == $user->phone_verification_otp
        ) {
            $user->is_phone_verified = true;
            $user->phone_verified_at = Carbon::now();

            Redis::publish(env('CHANNEL_PREFIX').'phone.verified', $user);
        }

        return response()->json(["success" => true], 200);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function changePassword(Request $request, $userid)
    {
        $input = $request->all();
        $user = $request->user();
        //$userid = Auth::guard('api')->user()->id;
        $rules = array(
            'old_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
        );

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json($validator->errors(), 422));
        }

        try {
            if ((Hash::check(request('old_password'), $user->password)) == false) {
                $arr = array("status" => 400, "message" => "Check your old password.", "data" => array());
            } elseif ((Hash::check(request('new_password'), $user->password)) == true) {
                $arr = array("status" => 400, "message" => "Please enter a password which is not similar then current password.", "data" => array());
            } else {
                User::find($userid)->update(['password' => Hash::make($input['new_password'])]);
                $arr = array("status" => 200, "message" => "Password updated successfully.", "data" => array());
            }
        } catch (\Exception $ex) {
            if (isset($ex->errorInfo[2])) {
                $msg = $ex->errorInfo[2];
            } else {
                $msg = $ex->getMessage();
            }
            $arr = array("status" => 400, "message" => $msg, "data" => array());
        }

        Redis::publish(env('CHANNEL_PREFIX').'password.change', User::find($userid));

        return response()->json($arr, $arr['status']);
    }

    public function forgotPassword(Request $request)
    {
        if (!$request->has('email')) {
            return response()->json(['Bad Request'], 400);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $user->password_reset_token = bin2hex(random_bytes(32));
        $user->save();

        Redis::publish(env('CHANNEL_PREFIX').'password.reset.request', $user);

        return response()->json(["success" => true], 200);
    }

    public function passwordReset(Request $request, $email, $token)
    {
        //FIND USER DETAILS
        $user = User::where('email', $email)
            ->where('password_reset_token', $token)
            ->firstOrFail();

        $validator = Validator::make(
            $request->all(),
            [
                'password' => 'required|min:6',
                'verify_password' => 'required|same:password',
            ]
        );

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json($validator->errors(), 422));
        }

        $user->password = bcrypt(request()->password);
        $user->last_password_reset_at = Carbon::now();
        $user->save();

        Redis::publish(env('CHANNEL_PREFIX').'password.reset.done', $user);

        return response()->json(["success" => true], 200);
    }
}
