<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/17
 * Time: 11:56
 */

namespace App\Models\Shop;


use App\Models\BaseModel;

class Adverts extends BaseModel
{
    protected $table='adverts';

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function detail()
    {
        return $this->hasMany(AdvertsDetail::class,'adverts_id','id');
    }

    public static function getData($adverts_id)
    {
        return self::where('id',$adverts_id)->where('status',1)
            ->where('start_date','<=',date('Y-m-d H:i:s'))
            ->where('end_date','>=',date('Y-m-d H:i:s'))
            ->with(['detail'=>function($qq){
                $qq->where('status',1)->select(['type','adverts_id','repeat1','period','attach_id'])->orderBy('sort');
            }])
            ->first(['id','name','start_date','end_date']);
    }
}