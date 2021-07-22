<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/6/27
 * Time: 14:30
 */

namespace App\Models\Shop;


use App\Models\BaseModel;
use App\Models\System\Attachment;

class SkuCover extends BaseModel
{
    protected $table='sku_cover';

    public function url()
    {
        return $this->belongsTo(Attachment::class,'attach_id','id');
    }
}