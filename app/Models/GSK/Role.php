<?php

namespace App\Models\GSK;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    //
    protected $table = 'gsk_role';

    protected $guarded=[];

    protected $casts=[
        'fun_resource'=>'array',
        'data_resource'=>'array'
    ];

    public function user()
    {
        return $this->hasMany(Employee::Class,'role_id','id');
    }
}
