<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/6/20
 * Time: 15:51
 */

namespace App\Models\ApiIot;


use App\Models\Base\DefineNote;
use App\Models\BaseModel;
use App\Models\Gashapon\Store;
use App\Models\Shop\Shop;
use App\Models\System\Attachment;

class DeviceRepair extends BaseModel
{
    protected $table='device_repair';

    protected $appends=['define_note','image_url'];
    public function shop()
    {
        return $this->belongsTo(Shop::class,'shop_id','id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class,'store_code','store_code');
    }

    public function iot()
    {
        return $this->belongsTo(Device::Class,'iot_id','iot_id');
    }

    public function note()
    {
        return $this->belongsTo(DefineNote::class,'repair_type_id','id');
    }

    public function getDefineNoteAttribute()
    {
        $dn=$this->repair_type_arr;
        if(!empty($dn)){
            return DefineNote::whereIn('id',explode(',',$dn))->pluck('des');
        }
        return null;
    }

    public function getImageUrlAttribute()
    {
        $imgarr=$this->image_arr;
        if(!empty($imgarr)){
            return Attachment::whereIn('id',explode(',',$imgarr))->pluck('file_url');
        }
        return null;
    }
}