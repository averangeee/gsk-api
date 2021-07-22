<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/24
 * Time: 17:13
 */

namespace App\Models\System;


use App\Models\BaseModel;

class Employee extends BaseModel
{
    public static $pwd='sl888888';

    protected $table='employee';

    protected $guarded=[];

    public function role()
    {
        return $this->belongsTo(Role::class,'role_id','id');
    }

//    public function __construct(){
//        $attributes=['password'];
//        $this->hidden = array_merge(
//            $this->hidden, is_array($attributes) ? $attributes : func_get_args()
//        );
//    }
}