<?php
/**
 * Created by PhpStorm.
 * User: shkjadmin
 * Date: 2019/5/12
 * Time: 19:10
 */

namespace App\Models\Gashapon;


use App\Models\Shop\ShopStore;

class Store extends BaseModel
{
    protected $table='store_sales';

    //protected $appends=['shop_name'];

    public function getShopNameAttribute()
    {
        $shop=ShopStore::where('store_code',trim($this->store_code))
            ->where('status',1)->with(['shop'=>function($qq){
                $qq->select(['id','name','parent_id']);
            }])->first(['shop_id','store_code']);
        return $shop;
    }

    public function bind()
    {
        return $this->belongsTo(ShopStore::class,'store_code','store_code');
    }
}