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
use App\Models\Gashapon\Store;
use App\Models\System\EmployeeWeixin;

class LockStatus extends BaseModel
{
    protected $table='lock_status';

    protected $guarded=[];

    public function device(){
        return $this->hasOne(Device::class,'iot_id','iot_id');
    }

    public function store(){
        return $this->hasOne(Store::class,'store_code','store_code');
    }

    public function user(){
        return $this->hasOne(EmployeeWeixin::class,'openid','openid');
    }
}