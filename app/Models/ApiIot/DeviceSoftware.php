<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/7/15
 * Time: 16:48
 */

namespace App\Models\ApiIot;


use App\Models\BaseModel;
use App\Models\System\Attachment;

class DeviceSoftware extends BaseModel
{
    protected $table='device_software';

    public function attach()
    {
        return $this->belongsTo(Attachment::class,'attach_id','id');
    }
}