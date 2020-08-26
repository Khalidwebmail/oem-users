<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => 'users'], function () {

    Route::post('login', 'API\V1\AuthController@login');
    Route::post('logout', 'API\V1\AuthController@logout');
    Route::get('me', 'API\V1\AuthController@me');

    Route::post('forgot-password', 'API\V1\UserController@forgotPassword');
    Route::post('password-reset/{email}/{token}', 'API\V1\UserController@passwordReset');
    Route::post('verify/{email}/{token}', 'API\V1\UserController@verify');

    Route::post('register', 'API\V1\AuthController@register');
    Route::post('register/{email}/{token}', 'API\V1\AuthController@completeRegistration');

//AUTH ROUTE
    Route::middleware('api')->group(function () {
        Route::post('refresh', 'API\V1\AuthController@refresh');

        Route::post('change-password/{userId}', 'API\V1\UserController@changePassword');

        // password reset

        // social media authentication

        Route::post('logout', 'API\V1\AuthController@logout');

        // User management Route
        Route::apiResource('users', 'API\V1\UserController');
        Route::patch('/users/{user}/active', 'API\V1\UserController@active');
        Route::patch('/users/{user}/suspend', 'API\V1\UserController@suspend');
        Route::post('/users/{user}/role', 'API\V1\UserController@assignRole');
        Route::post('/users/{user}/permission', 'API\V1\UserController@assignPermission');
    });

    Route::fallback(function () {
        return response()->json(['message' => 'Not Found.'], 404);
    });
});


