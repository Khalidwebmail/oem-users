<?php

namespace App\Observers;

use App\User;
use Illuminate\Support\Facades\Redis;

class UserObserver
{
    /**
     * Handle the user "created" event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function creating(User $user)
    {
        if (request()->has('password')) {
            $user->password = bcrypt(request()->password);
        }
        $user->email_verification_token = bin2hex(random_bytes(32));
        $user->phone_verification_otp = rand(100000, 999999);
    }

    /**
     * Handle the user "created" event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function created(User $user)
    {
        Redis::publish(env('CHANNEL_PREFIX').'created', $user);
    }

    /**
     * Handle the user "deleting" event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function deleting(User $user)
    {
        //
    }

    /**
     * Handle the user "deleted" event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function deleted(User $user)
    {
        Redis::publish(env('CHANNEL_PREFIX').'deleted', $user);
    }
}
