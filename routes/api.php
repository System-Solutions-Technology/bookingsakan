<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/invoke/{moduleName}/{modelName}/{id}/{methodName}', 'ReflectionController@call_method_json');

Route::group(['prefix'=>'user'], function() {
    Route::post('login','\Modules\User\Controllers\UserController@userLogin');
    Route::post('register','\Modules\User\Controllers\UserController@userRegister');
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

