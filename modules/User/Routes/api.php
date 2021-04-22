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

Route::group(['prefix'=>'user','middleware' => ['auth:api']],function() {
    Route::post('logout','UserController@logout_token');
    Route::post('profile', 'UserController@profile')->defaults('json', true);
    Route::post('refresh-token','ApiTokenController@update');
});

Route::group(['prefix'=>'user', 'middleware' => ['auth']],function(){
    Route::match(['get','post'],'/dashboard','UserController@dashboard');
    Route::post('/reloadChart','UserController@reloadChart');

    Route::post('/profile','UserController@profile')->defaults('json', true);
    Route::post('/profile/change-password','UserController@changePassword')->defaults('json', true);
    Route::get('/booking-history','UserController@bookingHistory');

    Route::post('/wishlist','UserWishListController@handleWishList');
    Route::get('/wishlist','UserWishListController@index');
    Route::get('/wishlist/remove','UserWishListController@remove');

});

Route::group(['prefix'=>'profile'],function(){
    Route::match(['get'],'/{id}','ProfileController@profile');
    Route::match(['get'],'/{id}/reviews','ProfileController@allReviews');
    Route::match(['get'],'/{id}/services','ProfileController@allServices');
});

//Newsletter
Route::post('newsletter/subscribe','UserController@subscribe')->name('newsletter.subscribe');
