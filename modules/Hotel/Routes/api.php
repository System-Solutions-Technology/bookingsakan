<?php
use \Illuminate\Support\Facades\Route;

Route::group(['prefix'=>config('hotel.hotel_route_prefix')],function(){
    Route::post('','HotelController@index')->defaults('json', true); //
    Route::post('rooms','HotelController@checkAvailability');
    Route::post('best','HotelController@getBestHotels')->defaults('json', true);;// Detail
    Route::post('{slug}','HotelController@detail_token')->defaults('json', true);;// Detail
});
