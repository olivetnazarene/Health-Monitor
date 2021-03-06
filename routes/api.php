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

Route::post('/login', 'Api\AuthController@login');
Route::get('/screenings', 'Api\ScreeningsController@index')->middleware('auth:api')->middleware('role:ADMIN');

Route::get('/users/{user}/screening', 'Api\ScreeningByErpController')->middleware('auth:api')->middleware('role:ADMIN');
