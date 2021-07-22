<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/29
 * Time: 18:38
 */

namespace App\Models\Order;


use App\Models\BaseModel;

class OrderRefundReason extends BaseModel
{
    protected $table='order_refund_reason';

    protected $casts=[
        'is_refund'=>'boolean'
    ];
}