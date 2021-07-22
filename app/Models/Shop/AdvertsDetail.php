<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/17
 * Time: 11:57
 */

namespace App\Models\Shop;


use App\Models\BaseModel;
use App\Models\System\Attachment;

class AdvertsDetail extends BaseModel
{
    protected $table='adverts_detail';

    protected $appends=['url'];

    public function attach()
    {
        return $this->belongsTo(Attachment::class,'attach_id','id');
    }

    public function getUrlAttribute()
    {
        $att=Attachment::find($this->attach_id);
        return $att?$att->file_url:'';
    }
}