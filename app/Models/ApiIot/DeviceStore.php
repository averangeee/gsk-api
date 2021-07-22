<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/12
 * Time: 13:33
 */

namespace App\Models\ApiIot;


use App\Models\BaseModel;
use App\Models\Gashapon\Store;

class DeviceStore extends BaseModel
{
    protected $table='device_store';

    protected $guarded=[];

    public function store()
    {
        return $this->belongsTo(Store::class,'store_code','store_code');
    }
}