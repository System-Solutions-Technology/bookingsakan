<?php
use \Illuminate\Support\Facades\Route;

Route::group(['prefix'=>config('hotel.hotel_route_prefix')],function(){
    Route::post('/','HotelController@index')->defaults('json', true); // Search
    Route::post('/{slug}','HotelController@detail')->defaults('json', true);;// Detail
});
