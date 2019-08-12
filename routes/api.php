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
Route::get('getServerKey', 'API\LogicController@getServerKey');
Route::post('register', 'API\LogicController@register');

Route::post('storeSecret', 'API\LogicController@storeSecret');
Route::get('getSecret', 'API\LogicController@getSecret');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
