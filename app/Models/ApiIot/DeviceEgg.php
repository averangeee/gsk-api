<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/10
 * Time: 11:10
 */

namespace App\Models\ApiIot;

use App\Models\BaseModel;
use App\Models\Gashapon\Sku;
use App\Models\Gashapon\Store;
use App\Models\Shop\Shop;


class DeviceEgg extends BaseModel
{
    protected $table='device_egg';

    protected $guarded=[];

    public static $eggError=[
        97=>'已复位，10分之后才能重新注册，或手动还原',
        98=>'未复位蛋仓'
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class,'shop_id','id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class,'store_code','store_code');
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class,'sku_code','sku_id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class,'iot_id','iot_id');
    }

    public function errorDes(){
        return $this->belongsTo(DeviceErrorLogDes::class,'error_status','error_id_bit');
    }
}