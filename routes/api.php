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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['middleware' => 'authToken', 'prefix' => 'gameboy',], function () {
    Route::post('/creategroup', 'App\Http\Controllers\APIController@creategroup');
    Route::post('/changeusergrp', 'App\Http\Controllers\APIController@changeplayergrp');
    Route::post('/getgrouplist', 'App\Http\Controllers\APIController@getgrouplist');


    
    
    Route::post('/operator/info', 'App\Http\Controllers\APIController@getoperatorInfo');

    Route::post('/user/create', 'App\Http\Controllers\APIController@createplayer');
    Route::post('/user/charge', 'App\Http\Controllers\APIController@transfer');
    Route::post('/user/withdrawall', 'App\Http\Controllers\APIController@withdrawall');
    Route::post('/user/balance', 'App\Http\Controllers\APIController@getbalance');
    Route::post('/user/gamelink', 'App\Http\Controllers\APIController@startgame');

    Route::post('/user/terminate', 'App\Http\Controllers\APIController@terminategame');

    Route::post('/game/halls', 'App\Http\Controllers\APIController@getproviders');
    Route::post('/game/list', 'App\Http\Controllers\APIController@getgamelist');

    Route::post('/round/view', 'App\Http\Controllers\APIController@getgamerounds');

    Route::post('/getroundinfo', 'App\Http\Controllers\APIController@getroundinfo')->middleware('throttle:60,1');
    Route::post('/payout', 'App\Http\Controllers\APIController@payout');
    Route::post('/bonuscreate', 'App\Http\Controllers\APIController@bonuscreate');
    Route::post('/bonuslist', 'App\Http\Controllers\APIController@bonuslist');
});