<?php

namespace App\Models\GSK;

use Illuminate\Database\Eloquent\Model;

class ManagerReset extends Model
{
    //
    protected $table = 'gsk_manager_reset';

    public function city()
    {
        return $this->hasOne(City::Class,'id','city_id');
    }


    public function employee_before()
    {
        return $this->hasOne(employee::class, 'id', 'yq_manager_id');
    }

    public function employee_jieshou()
    {
        return $this->hasOne(employee::class, 'id', 'user_id');
    }

    public function employee()
    {
        return $this->hasOne(employee::class, 'id', 'yq_manager_id');
    }
    public function address()
    {
        return $this->hasOne(ck::class, 'id', 'ck_id');
    }



}
