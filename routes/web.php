<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/wap/wx/outh', 'WxOuthController@outh')->middleware('wechat.oauth');

Route::get('/', 'WebApi\IndexController@index');
Route::get('/api', function () {
    return 'hehe';
});
