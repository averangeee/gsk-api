<?php

namespace App\Models\GSK;

use Illuminate\Database\Eloquent\Model;

class WeixiuApply extends Model
{
    //
    protected $table = 'gsk_weixiu_apply';

    protected $guarded=[];

    public function employee()
    {
        return $this->hasOne(employee::class, 'id', 'operator');
    }

    public function city()
    {
        return $this->hasOne(City::Class,'id','city_id');
    }

    public function device()
    {
        return $this->hasOne(Device::Class,'gsk_code','gsk_code');
    }

}
