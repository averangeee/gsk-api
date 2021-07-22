<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/15
 * Time: 11:08
 */

namespace App\Models\ApiIot;


use App\Models\BaseModel;

class DeviceMessage extends BaseModel
{
    protected $table='device_message';

    protected $guarded=[];

    protected $casts=[
        'content'=>'json',
        'result'=>'boolean',
        'result_content'=>'json'
    ];

    public function messageRead()
    {
        return $this->hasMany(DeviceMessageRead::class,'msg_id','msg_id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class,'device_name','device_name');
    }
}