<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/12
 * Time: 13:37
 */

namespace App\Models\Order;


use App\Models\ApiIot\Device;
use App\Models\BaseModel;

class GoodsSupplyStatus extends BaseModel
{
    protected $table='goods_supply_status';

    protected $guarded=[];

    public function device(){
        return $this->hasOne(Device::class,'iot_id','iot_id');
    }
}