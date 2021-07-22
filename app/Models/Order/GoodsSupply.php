<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/12
 * Time: 13:36
 */

namespace App\Models\Order;


use App\Models\BaseModel;
use App\Models\Gashapon\Sku;
use App\Models\Gashapon\Store;
use App\Models\System\Employee;

class GoodsSupply extends BaseModel
{
    protected $table='goods_supply';

    protected $guarded=[];

    public function supplyStatus()
    {
        return $this->hasOne(GoodsSupplyStatus::Class,'supply_id','supply_id');
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class,'sku_code','sku_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class,'store_code','store_code');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class,'created_code','employee_code');
    }
}