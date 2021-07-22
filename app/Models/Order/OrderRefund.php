<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/29
 * Time: 16:02
 */

namespace App\Models\Order;


use App\Models\Base\DefineNote;
use App\Models\BaseModel;

class OrderRefund extends BaseModel
{
    protected $table='order_refund';

    protected $appends=['is_show'];

    protected $casts=[
        'is_show'=>'boolean'
    ];

    public static $errorName=[
        0=>'无',
        1=>'锁无法打开',
        2=>'锁打开用户未扭动',
        3=>'卡蛋',
        4=>'无蛋'
    ];

    public function getIsShowAttribute(){
        return false;
    }

    public function order()
    {
        return $this->belongsTo(Order::class,'order_sn','order_sn');
    }

    public function note()
    {
        return $this->belongsTo(DefineNote::class,'refund_type_id','id');
    }

    public function reason()
    {
        return $this->hasMany(OrderRefundReason::class,'refund_id','id');
    }


}