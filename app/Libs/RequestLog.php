<?php
/**
 * Created by PhpStorm.
 * User: shkjadmin
 * Date: 2018/9/20
 * Time: 17:06
 */

namespace App\Libs;


class RequestLog
{
    public static $log=[
        'res_device_note'=>'设置设备别名',
        'res_device_status2'=>'更改设备状态',

        'res_device_msg_query'=>'设备消息',
        'res_device_msg_read'=>'设备消息标记已读',
        'res_device_msg_delete'=>'删除设备消息',

        'res_device_log_query'=>'设备日志',

        //订单管理
        'res_order_refund_query'=>'退款申请',
        'res_order_refund_img'=>'退款图片',
        'res_order_refund_exec'=>'退款审核',
        'res_order_refund_del'=>'退款删除',

        'sys_user_search'=>'查询用户',
        'sys_user_add'=>'新增用户',
        'sys_user_update'=>'修改用户',
        'sys_user_delete'=>'删除用户',
        'sys_user_update_status'=>'冻结用户',

        'sys_role_add'=>'新增角色',
        'sys_role_update'=>'修改角色',
        'sys_role_delete'=>'删除角色',
        'sys_log_search'=>'查询日志',

        'sys_menu_search'=>'菜单查询',
        'sys_menu_add'=>'添加菜单',
        'sys_menu_edit'=>'修改菜单',

        'iot_supply_open_lock'=>'上货开锁',
        'iot_adverts_update'=>'广告更新',

        'store_query'=>'门店列表',
        'store_bind'=>'绑定门店',

        'order_sku_list'=>'蛋仓产品列表',
        'order_create'=>'创建订单'
    ];
}
