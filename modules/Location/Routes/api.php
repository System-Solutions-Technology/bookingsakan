<?php

use \Illuminate\Support\Facades\Route;



Route::group(['prefix'=>config('location.location_route_prefix'), 'middleware'=>'auth:api'],function(){
    Route::post('/', 'LocationController@getAllLocations')->defaults('json', true);
});
