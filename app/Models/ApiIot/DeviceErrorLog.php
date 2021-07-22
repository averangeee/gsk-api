<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/7/15
 * Time: 10:44
 */

namespace App\Models\ApiIot;


use App\Models\BaseModel;
use App\Models\Gashapon\Store;

class DeviceErrorLog extends BaseModel
{
    protected $table='device_error_log';

    //错误状态描述，加入语言控制
    public function des()
    {
        $return=$this->belongsTo(DeviceErrorLogDes::class,'error_id','error_id_bit');
        return $return;
    }

    public function iot()
    {
        return $this->belongsTo(Device::class,'iot_id','iot_id');
    }

    public function egg()
    {
        return $this->belongsTo(DeviceEgg::class,'egg_code','egg_code');
    }

    public function store()
    {
        return $this->belongsTo(Store::class,'store_code','store_code');
    }
}