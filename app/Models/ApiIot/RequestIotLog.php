<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/24
 * Time: 18:30
 */

namespace App\Models\ApiIot;


use App\Models\BaseModel;

class RequestIotLog extends BaseModel
{
    protected $table='request_iot_log';

    protected $guarded=[];

    protected $casts=[
        'params'=>'json'
    ];

    public function res()
    {
        return $this->belongsTo(ResponseIotLog::class,'id','request_log_id');
    }

    public function iot()
    {
        return $this->belongsTo(Device::class,'iot_id','iot_id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class,'device_name','device_code');
    }
}