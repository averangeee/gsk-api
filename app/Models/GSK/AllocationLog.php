<?php

namespace App\Models\GSK;

use Illuminate\Database\Eloquent\Model;

class AllocationLog extends Model
{
    //
    protected $table = 'gsk_allocation_log';

    protected $guarded=[];

    public function employee()
    {
        return $this->hasOne(Employee::Class,'id','operater');
    }

    public function city()
    {
        return $this->hasOne(City::Class,'id','city_id');
    }

    public function device()
    {
        return $this->hasOne(Device::Class,'gsk_code','gsk_code');
    }
    public function employee_two()
    {
        return $this->hasOne(Employee::Class,'id','user_id');
    }






}
