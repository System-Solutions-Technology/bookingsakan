<?php



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


use Illuminate\Support\Facades\Route;

Route::group(['prefix'=>'user','middleware' => ['auth:api']], function() {
    Route::post('logout','UserController@logout_token');
    Route::post('current-profile', 'UserController@profile_token');
    Route::post('refresh-token','ApiTokenController@update');
    Route::post('change-password','UserController@changePassword_token');
    Route::post('update-profile', 'UserController@update_profile_token');
    Route::post('hotel-wishlist','UserWishListController@getUserWishlist')->defaults('json', true);
    Route::post('handle-wishlist','UserWishListController@handleWishList')->defaults('json', true);

});
