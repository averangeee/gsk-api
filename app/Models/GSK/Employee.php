<?php

namespace App\Models\GSK;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    //
    public static $pwd='888888';
    protected $guarded=[];
    protected $table = 'gsk_employee';

    public function role()
    {
        return $this->belongsTo(Role::class,'role_id','id');
    }

  /*  public function role()
    {
        return $this->hasOne(Role::class,'id','role_id');
    }*/
    public function city()
    {
        return $this->hasOne(City::class, 'id', 'city_id');
    }
    public function employee()
    {
        return $this->hasOne(employee::class, 'id', 'yq_manager_id');
    }
}
