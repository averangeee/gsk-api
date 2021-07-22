<?php
/**
 * Created by PhpStorm.
 * User: shkjadmin
 * Date: 2019/5/12
 * Time: 19:08
 */

namespace App\Models\Gashapon;



use App\Models\Mh\SkuTypes;
use App\Models\Shop\SkuCover;

class Sku extends BaseModel
{
    protected $table='sku';

    protected $appends=['file_url'];

    public function cover()
    {
        return $this->belongsTo(SkuCover::class,'sku_id','sku_code');
    }

    public function getFileUrlAttribute()
    {
        $cover=SkuCover::where('sku_code',$this->sku_id)->with(['url'=>function($url){
            $url->select(['id','file_url']);
        }])->first(['sku_code','attach_id']);
        if(isset($cover->url->file_url)){
            return $cover->url->file_url;
        }else{
            return null;
        }
    }

}