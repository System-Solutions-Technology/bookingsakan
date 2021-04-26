<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::group(['prefix'=>config('booking.booking_route_prefix'), 'middleware' => 'auth:api'],function() {
    Route::post('/','BookingController@details')->defaults('json', true);
    Route::post('book', 'BookingController@addToCart');
    Route::post('checkout', 'BookingController@doCheckout');
    Route::post('/{code}','BookingController@detail')->defaults('json', true);
});
