<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/17
 * Time: 11:56
 */

namespace App\Models\GSK;


use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'gsk_device';

    public function city()
    {
        return $this->hasOne(City::Class,'id','city_id');
    }

    public function address()
    {
        return $this->hasOne(Ck::Class,'id','address_id');
    }
    public function employee()
    {
        return $this->hasOne(employee::class, 'id', 'yq_manager_id');
    }

    public function jyemployee()
    {
        return $this->hasOne(employee::class, 'id', 'yq_manager_id_two');
    }

    public function provice()
    {
        return $this->hasOne(City::Class,'id','provice_id');
    }






}
