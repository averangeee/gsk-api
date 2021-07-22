<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/6/5
 * Time: 14:32
 */

namespace App\Models\Message;


use App\Models\ApiIot\Device;
use App\Models\BaseModel;

class Messages extends BaseModel
{
    protected $table='messages';

    public function device()
    {
        return $this->belongsTo(Device::class,'iot_id','iot_id');
    }

    public function status()
    {
        return $this->hasMany(MessagesStatus::class,'messages_id','id');
    }
}