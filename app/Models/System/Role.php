<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/28
 * Time: 16:14
 */

namespace App\Models\System;


use App\Models\BaseModel;

class Role extends BaseModel
{
    protected $table='role';

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