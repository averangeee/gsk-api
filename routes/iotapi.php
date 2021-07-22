<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/6/24
 * Time: 9:41
 */
use Illuminate\Http\Request;
//Iot
//注册
//'iot.log','register'
Route::get('/v3/iot/register', 'ApiIotV3\RegisterController@register')->middleware(['iot.log','register'])->name('iot_register');

//接口
//sign
Route::group(['middleware' => ['iot.log','sign'],'namespace'=>'ApiIotV3','prefix'=>'v3/iot'], function () {
    //上货
    Route::get('/lock/status', 'MaintainController@lockStatus')->name('iot_supply_lock_status'); //开锁状态同步

    //购买
    Route::get('/order/status', 'OrderController@orderStatus')->name('iot_order_status'); //订单状态

    //设备
    Route::get('/sync', 'SyncIotController@synIotEgg')->name('iot_syn_egg');//蛋仓同步
    Route::get('/message/read', 'MessageController@writeOffMsg')->name('iot_msg_read');//消息核销
    Route::get('/adverts', 'AdvertsController@getAdverts')->name('iot_adverts_query');//广告拉取

    //报修

    //异常预警
    Route::get('/warn', 'WarnController@warn')->name('iot_warn');//异常预警


});
