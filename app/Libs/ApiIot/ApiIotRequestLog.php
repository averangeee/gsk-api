<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/9
 * Time: 16:38
 */

namespace App\Libs\ApiIot;


class ApiIotRequestLog
{
    public static $routerFun=[
        'iot_register'=>'设备注册',
        'iot_supply_lock_status'=>'上货开/关蛋仓',
        'iot_supply_syn_sku'=>'上货同步产品信息',

        'iot_buy_check'=>'购买校验',
        'iot_order_status'=>'订单状态同步',

        'iot_syn_status'=>'设备状态同步',
        'iot_syn_egg'=>'蛋仓同步',
        'iot_msg_read'=>'消息核销',
        'iot_adverts_query'=>'广告拉取',

        'iot_warn'=>'异常预警',
    ];
}