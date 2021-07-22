<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/15
 * Time: 15:02
 */

namespace App\Models\Shop;


use App\Models\BaseModel;
use App\Models\Gashapon\Store;

class ShopStore extends BaseModel
{
    protected $table='shop_store';

    protected $guarded=[];

    public function shop()
    {
        return $this->belongsTo(Shop::class,'shop_id','id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class,'store_code','store_code');
    }
}